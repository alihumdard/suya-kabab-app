<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Order;
use App\Models\User;
use App\Models\PendingOrder;
use GuzzleHttp\Client;

class FlutterwavePaymentService
{
    /**
     * Custom 3DES encryption for Flutterwave (matching SDK implementation)
     * Using 3DES-ECB mode as required by Flutterwave API
     */
    private function encrypt3Des($data, $key)
    {
        // Ensure key is 24 bytes for 3DES
        $key = substr($key, 0, 24);
        $encData = openssl_encrypt($data, 'DES-EDE3-ECB', $key, OPENSSL_RAW_DATA);
        return base64_encode($encData);
    }

    /**
     * Charge a card directly (initial request)
     */
    public function chargeCard($cardData)
    {
        try {
            $secretKey = config('services.flutterwave.secret_key');
            $publicKey = config('services.flutterwave.public_key');
            $encryptionKey = config('services.flutterwave.encryption_key');

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $payload = [
                "card_number" => $cardData['card_number'],
                "cvv" => $cardData['cvv'],
                "expiry_month" => (string) $cardData['expiry_month'],
                "expiry_year" => (string) $cardData['expiry_year'],
                "currency" => $cardData['currency'],
                "amount" => (string) $cardData['amount'],
                "email" => $cardData['email'],
                "fullname" => $cardData['fullname'],
                "phone_number" => $cardData['phone_number'],
                "tx_ref" => $cardData['tx_ref'],
                "redirect_url" => $cardData['redirect_url'],
            ];

            // Add authorization if provided (for PIN, AVS, etc.)
            if (isset($cardData['authorization'])) {
                $payload['authorization'] = $cardData['authorization'];
            }

            // 🔑 Encrypt payload using 3DES encryption (matching SDK)
            $encryptedData = $this->encrypt3Des(json_encode($payload), $encryptionKey);

            $body = [
                "client" => $encryptedData,
                "public_key" => $publicKey,
            ];

            $response = $client->post("https://api.flutterwave.com/v3/charges?type=card", [
                'headers' => [
                    'Authorization' => "Bearer {$secretKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            $result = json_decode($response->getBody(), true);

            // Flutterwave may ask for PIN/AVS authorization
            if (isset($result['meta']['authorization'])) {
                $transactionId = $result['data']['id'] ?? $result['data']['tx_ref'] ?? null;

                // Try multiple ways to get flw_ref
                $flwRef = null;
                if (isset($result['flw_ref']) && $result['flw_ref']) {
                    $flwRef = $result['flw_ref'];
                } elseif (isset($result['data']['flw_ref']) && $result['data']['flw_ref']) {
                    $flwRef = $result['data']['flw_ref'];
                } elseif ($transactionId) {
                    // Generate mock flw_ref for sandbox if not provided
                    $flwRef = 'FLW-MOCK-' . md5($transactionId . time());
                }

                return [
                    'success' => false,
                    'requires_verification' => true,
                    'authorization' => $result['meta']['authorization'],
                    'transaction_id' => $transactionId,
                    'tx_ref' => $cardData['tx_ref'],
                    'message' => $result['message'] ?? 'Authorization required',
                    'data' => [
                        'status' => 'requires_verification',
                        'mode' => $result['meta']['authorization']['mode'] ?? 'unknown',
                        'instruction' => $result['meta']['authorization']['instruction'] ?? 'Additional verification required',
                        'reference' => $cardData['tx_ref'],
                        'requires_verification' => true,
                        'verification_type' => $result['meta']['authorization']['mode'] ?? 'unknown',
                        'flw_ref' => $flwRef,
                        'transaction_id' => $transactionId,
                        'meta' => $result['meta'] ?? null
                    ]
                ];
            }

            // Check if payment was successful
            if (isset($result['status']) && $result['status'] === 'success') {
                $data = $result['data'];

                if (isset($data['status']) && $data['status'] === 'successful') {
                    return [
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'data' => [
                            'status' => 'successful',
                            'reference' => $data['reference'] ?? $cardData['tx_ref'],
                            'transaction_id' => $data['id'] ?? null,
                            'amount' => $data['amount'] ?? $cardData['amount'],
                            'currency' => $data['currency'] ?? $cardData['currency'],
                            'payment_type' => $data['payment_type'] ?? 'card'
                        ]
                    ];
                }

                // Payment failed
                if (isset($data['status']) && $data['status'] === 'failed') {
                    return [
                        'success' => false,
                        'message' => 'Payment failed: ' . ($data['gateway_response'] ?? 'Unknown error'),
                        'data' => [
                            'status' => 'failed',
                            'reference' => $data['reference'] ?? $cardData['tx_ref'],
                            'transaction_id' => $data['id'] ?? null,
                            'gateway_response' => $data['gateway_response'] ?? null
                        ]
                    ];
                }
            }

            return [
                'success' => $result['status'] === 'success',
                'data' => $result,
                'message' => $result['message'] ?? 'Charge attempted'
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave card charge error: ' . $e->getMessage(), [
                'tx_ref' => $cardData['tx_ref'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $errorMessage = $e->getMessage();

            // First try to extract the actual Flutterwave error message from the response
            $extractedMessage = null;
            if (preg_match('/\{"status":"error","message":"([^"]+)"/', $errorMessage, $matches)) {
                $extractedMessage = $matches[1];
            } elseif (preg_match('/"message":"([^"]+)"/', $errorMessage, $matches)) {
                $extractedMessage = $matches[1];
            }

            // If we extracted a message, use it; otherwise use the original
            if ($extractedMessage) {
                $errorMessage = $extractedMessage;
            }

            // Now provide user-friendly messages for specific error types
            if (strpos($errorMessage, 'Fraudulent') !== false) {
                $errorMessage = 'Transaction flagged as fraudulent. Please use valid test card numbers for sandbox testing.';
            } elseif (strpos($errorMessage, 'Do Not Honour') !== false) {
                $errorMessage = 'Card declined by your bank. This may be due to insufficient funds, card restrictions, or security settings. Please try a different card or contact your bank.';
            } elseif (strpos($errorMessage, 'Insufficient Funds') !== false) {
                $errorMessage = 'Insufficient funds on the card. Please check your account balance or try a different card.';
            } elseif (strpos($errorMessage, 'Invalid Card') !== false || strpos($errorMessage, 'invalid card') !== false) {
                $errorMessage = 'Invalid card details provided. Please check your card number, expiry date, and CVV.';
            } elseif (strpos($errorMessage, 'Expired Card') !== false || strpos($errorMessage, 'expired') !== false) {
                $errorMessage = 'Your card has expired. Please use a valid card with a future expiry date.';
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => [
                    'status' => 'error',
                    'reference' => $cardData['tx_ref'] ?? null,
                    'error' => $e->getMessage(),
                ]
            ];
        }
    }

    /**
     * Resubmit card payment with PIN (or AVS)
     */
    public function completeCardPaymentWithPin($cardData, $pin = null, $avsData = null)
    {
        try {
            $secretKey = config('services.flutterwave.secret_key');
            $publicKey = config('services.flutterwave.public_key');
            $encryptionKey = config('services.flutterwave.encryption_key');

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $payload = [
                "card_number" => $cardData['card_number'],
                "cvv" => $cardData['cvv'],
                "expiry_month" => (string) $cardData['expiry_month'],
                "expiry_year" => (string) $cardData['expiry_year'],
                "currency" => $cardData['currency'],
                "amount" => (string) $cardData['amount'],
                "email" => $cardData['email'],
                "fullname" => $cardData['fullname'],
                "phone_number" => $cardData['phone_number'],
                "tx_ref" => $cardData['tx_ref'],
                "redirect_url" => $cardData['redirect_url'],
            ];

            // Add authorization based on what's required
            if ($pin) {
                $payload["authorization"] = [
                    "mode" => "pin",
                    "pin" => $pin
                ];
            } elseif ($avsData) {
                $payload["authorization"] = [
                    "mode" => "avs_noauth",
                    "city" => $avsData['city'],
                    "address" => $avsData['address'],
                    "state" => $avsData['state'],
                    "country" => $avsData['country'],
                    "zipcode" => $avsData['zipcode']
                ];
            }

            // 🔑 Encrypt payload using 3DES encryption (matching SDK)
            $encryptedData = $this->encrypt3Des(json_encode($payload), $encryptionKey);

            $body = [
                "client" => $encryptedData,
                "public_key" => $publicKey,
            ];

            $response = $client->post("https://api.flutterwave.com/v3/charges?type=card", [
                'headers' => [
                    'Authorization' => "Bearer {$secretKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            $result = json_decode($response->getBody(), true);

            // After PIN, Flutterwave might request OTP or return auth_url for 3DS
            if (isset($result['meta']['authorization'])) {
                $transactionId = $result['data']['id'] ?? $result['data']['tx_ref'] ?? null;

                // Try multiple ways to get flw_ref
                $flwRef = null;
                if (isset($result['flw_ref']) && $result['flw_ref']) {
                    $flwRef = $result['flw_ref'];
                } elseif (isset($result['data']['flw_ref']) && $result['data']['flw_ref']) {
                    $flwRef = $result['data']['flw_ref'];
                } elseif ($transactionId) {
                    // Generate mock flw_ref for sandbox if not provided
                    $flwRef = 'FLW-MOCK-' . md5($transactionId . time());
                }

                return [
                    'success' => false,
                    'requires_verification' => true,
                    'authorization' => $result['meta']['authorization'],
                    'transaction_id' => $transactionId,
                    'tx_ref' => $cardData['tx_ref'],
                    'message' => $result['message'] ?? 'Further verification required',
                    'data' => [
                        'status' => 'requires_verification',
                        'mode' => $result['meta']['authorization']['mode'] ?? 'unknown',
                        'instruction' => $result['meta']['authorization']['instruction'] ?? 'Additional verification required',
                        'reference' => $cardData['tx_ref'],
                        'requires_verification' => true,
                        'verification_type' => $result['meta']['authorization']['mode'] ?? 'unknown',
                        'flw_ref' => $flwRef,
                        'transaction_id' => $transactionId,
                        'meta' => $result['meta'] ?? null
                    ]
                ];
            }

            // Check if payment was successful after PIN
            if (isset($result['status']) && $result['status'] === 'success') {
                $data = $result['data'];

                if (isset($data['status']) && $data['status'] === 'successful') {
                    return [
                        'success' => true,
                        'message' => 'Payment completed successfully',
                        'data' => [
                            'status' => 'successful',
                            'reference' => $data['reference'] ?? $cardData['tx_ref'],
                            'transaction_id' => $data['id'] ?? null,
                            'amount' => $data['amount'] ?? $cardData['amount'],
                            'currency' => $data['currency'] ?? $cardData['currency'],
                            'payment_type' => 'card'
                        ]
                    ];
                }
            }

            return [
                'success' => $result['status'] === 'success',
                'data' => $result,
                'message' => $result['message'] ?? 'Charge attempted'
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave PIN authorization error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'PIN authorization failed: ' . $e->getMessage(),
                'data' => [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Validate OTP for card payment
     */
    public function validateCharge($otp, $flwRef)
    {
        try {
            $secretKey = config('services.flutterwave.secret_key');

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $payload = [
                'otp' => $otp,
                'flw_ref' => $flwRef
            ];

            $response = $client->post("https://api.flutterwave.com/v3/validate-charge", [
                'headers' => [
                    'Authorization' => "Bearer {$secretKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody(), true);



            // Check if validation was successful
            if (isset($result['status']) && $result['status'] === 'success') {
                $data = $result['data'];

                if (isset($data['status']) && $data['status'] === 'successful') {
                    return [
                        'success' => true,
                        'message' => 'Payment validated successfully',
                        'data' => [
                            'status' => 'successful',
                            'reference' => $data['reference'] ?? $data['tx_ref'] ?? null,
                            'transaction_id' => $data['id'] ?? null,
                            'amount' => $data['amount'] ?? null,
                            'currency' => $data['currency'] ?? null,
                            'payment_type' => 'card'
                        ]
                    ];
                }

                // Payment still pending or failed
                return [
                    'success' => false,
                    'message' => 'Payment validation failed: ' . ($data['processor_response'] ?? 'Unknown error'),
                    'data' => [
                        'status' => $data['status'] ?? 'failed',
                        'reference' => $data['reference'] ?? $data['tx_ref'] ?? null,
                        'transaction_id' => $data['id'] ?? null,
                        'processor_response' => $data['processor_response'] ?? null
                    ]
                ];
            }

            return [
                'success' => false,
                'data' => $result,
                'message' => $result['message'] ?? 'Validation failed'
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave OTP validation error: ' . $e->getMessage(), [
                'flw_ref' => $flwRef,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'OTP validation failed: ' . $e->getMessage(),
                'data' => [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Verify transaction after OTP / 3DS
     */
    public function verifyTransaction($transactionId)
    {
        try {
            $secretKey = config('services.flutterwave.secret_key');

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $url = "https://api.flutterwave.com/v3/transactions/{$transactionId}/verify";

            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$secretKey}"
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            return $result;
        } catch (\Exception $e) {
            Log::error('Flutterwave verify error: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Verification failed: ' . $e->getMessage(),
                'error' => true
            ];
        }
    }

    /**
     * Initialize a payment transaction (for redirect-based payments)
     */
    public function initializePayment(Order $order, User $user, $redirectUrl = null)
    {
        try {
            $paymentData = [
                'tx_ref' => $order->order_number . '_' . time(),
                'amount' => $order->total_amount,
                'currency' => $order->currency ?? 'NGN',
                'redirect_url' => $redirectUrl ?? url('/payment/callback'),
                'customer' => [
                    'email' => $user->email,
                    'phonenumber' => $user->phone,
                    'name' => $user->name
                ],
                'customizations' => [
                    'title' => 'Suya Kabab Payment',
                    'description' => 'Payment for order #' . $order->order_number,
                    'logo' => asset('images/logo.png')
                ],
                'meta' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ],
                'payment_options' => 'card,ussd,banktransfer,account,mpesa,mobilemoneyghana,mobilemoneyuganda,mobilemoneyzambia,mobilemoneyrwanda,mpesa,barter,payattitude,paypal,flutterwave,1voucher,all'
            ];

            // Make HTTP request to Flutterwave API
            $secretKey = config('services.flutterwave.secret_key');
            $url = 'https://api.flutterwave.com/v3/payments';

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $paymentData,
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['status']) && $body['status'] === 'success') {
                // Update order with payment reference
                $order->update([
                    'payment_reference' => $body['data']['tx_ref'] ?? $order->order_number,
                ]);
                return [
                    'success' => true,
                    'data' => [
                        'payment_url' => $body['data']['link'] ?? null,
                        'reference' => $body['data']['tx_ref'] ?? null,
                        'status' => 'pending'
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Failed to initialize payment',
                'data' => $body
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave payment initialization error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process refund for a payment
     */
    public function processRefund($paymentId, $amount = null, $reason = 'Customer request')
    {
        try {
            $payment = Payment::findOrFail($paymentId);
            $secretKey = config('services.flutterwave.secret_key');
            $transactionId = $payment->transaction_id;

            if (empty($transactionId)) {
                Log::error('Payment transaction_id is empty', [
                    'payment_id' => $payment->id,
                    'payment_data' => $payment->toArray()
                ]);
                return [
                    'success' => false,
                    'message' => 'Payment transaction ID not found - cannot process refund'
                ];
            }

            $refundData = [
                'amount' => $amount ?? $payment->amount,
                'comment' => $reason
            ];

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $response = $client->post("https://api.flutterwave.com/v3/transactions/{$transactionId}/refund", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $refundData,
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // Create refund record
                $refund = Refund::create([
                    'user_id' => $payment->user_id,
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $amount ?? $payment->amount,
                    'currency' => $payment->currency ?? 'NGN',
                    'reason' => $reason,
                    'status' => 'processing', // Start as processing, webhook will update to successful/failed
                    'reference' => 'REFUND_' . time() . '_' . $payment->id,
                    'transaction_id' => $result['data']['id'] ?? $result['data']['refund_id'] ?? null,
                    'gateway_response' => $result['message'] ?? 'Refund initiated',
                    'gateway_data' => $result['data'] ?? []
                ]);

                // Check if refund is already completed (some gateways process instantly)
                $refundStatus = $result['data']['status'] ?? 'pending';
                if (in_array($refundStatus, ['completed', 'successful', 'success'])) {
                    $refund->markAsSuccessful($result['data'] ?? []);
                }

                return [
                    'success' => true,
                    'message' => 'Refund initiated successfully',
                    'data' => [
                        'refund_id' => $refund->id,
                        'reference' => $refund->reference,
                        'amount' => $refund->amount,
                        'status' => $refund->status,
                        'transaction_id' => $refund->transaction_id,
                        'gateway_status' => $refundStatus ?? 'unknown'
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to process refund',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave refund error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Refund processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment status by reference
     */
    public function getPaymentStatus($reference)
    {
        try {
            $secretKey = config('services.flutterwave.secret_key');

            $client = new Client([
                'timeout' => 30,
                'verify' => false
            ]);

            $url = "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref={$reference}";

            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$secretKey}"
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            return $result;
        } catch (\Exception $e) {
            Log::error('Flutterwave payment status error: ' . $e->getMessage(), [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment status check failed: ' . $e->getMessage(),
                'error' => true
            ];
        }
    }

    /**
     * Handle Flutterwave webhook
     */
    public function handleWebhook($payload, $signature)
    {
        try {
            // Verify webhook signature (optional but recommended)
            if (!$this->verifyWebhookSignature($payload, $signature)) {
                Log::error('Webhook signature verification failed - rejecting webhook');
                return false;
            }

            $event = $payload['event'] ?? '';
            $data = $payload['data'] ?? [];

            // Flutterwave sends 'tx_ref' not 'reference' in webhook
            $reference = $data['reference'] ?? $data['tx_ref'] ?? '';

            switch ($event) {
                case 'charge.completed':
                    if ($data['status'] === 'successful') {
                        return $this->processSuccessfulPayment($reference, $data);
                    }
                    break;

                case 'transfer.completed':
                    // Handle transfer completion
                    break;

                case 'refund.completed':
                case 'refund.successful':
                case 'refund.failed':
                    // Handle refund completion (multiple possible event names)
                    return $this->processRefundWebhook($data);

                default:
                    // Unhandled webhook event
                    break;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Process successful payment from webhook
     */
    private function processSuccessfulPayment($reference, $data)
    {
        try {
            // Find order by payment reference
            $order = Order::where('payment_reference', $reference)->first();

            if ($order) {
                // Order already exists, just update payment status
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'pending'  // Use pending status, admin can update to confirmed later
                ]);

                // Create/update payment record
                Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'user_id' => $order->user_id,
                        'transaction_id' => $data['id'] ?? null,
                        'reference' => $reference,
                        'amount' => $order->total_amount,
                        'currency' => $data['currency'] ?? 'NGN',
                        'payment_method' => 'card',
                        'status' => 'successful',
                        'gateway_response' => 'Payment successful via webhook',
                        'gateway_data' => $data,
                        'paid_at' => now(),
                    ]
                );

                return true;
            }

            // Order doesn't exist - try to create it from cached data or webhook metadata
            return $this->createOrderFromWebhook($reference, $data);
        } catch (\Exception $e) {
            Log::error('Webhook payment processing error: ' . $e->getMessage(), [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create order from webhook when order doesn't exist yet
     */
    private function createOrderFromWebhook($reference, $data)
    {
        try {
            Log::info('Creating order from webhook', [
                'reference' => $reference,
                'transaction_id' => $data['id'] ?? null,
                'customer_email' => $data['customer']['email'] ?? 'unknown'
            ]);

            // First try to get pending order data
            $pendingOrder = PendingOrder::forReference($reference)->first();

            Log::info('Pending order lookup result', [
                'reference' => $reference,
                'pending_order_found' => !empty($pendingOrder),
                'pending_order_id' => $pendingOrder->id ?? null,
                'status' => $pendingOrder->status ?? null
            ]);

            $orderData = null;

            if ($pendingOrder && !$pendingOrder->isExpired()) {
                // Use data from pending order
                $orderData = $pendingOrder->order_data;
                Log::info('Using order data from pending order', [
                    'pending_order_id' => $pendingOrder->id,
                    'order_data_keys' => array_keys($orderData)
                ]);
            } else {
                if ($pendingOrder && $pendingOrder->isExpired()) {
                    Log::warning('Pending order found but expired', [
                        'pending_order_id' => $pendingOrder->id,
                        'expires_at' => $pendingOrder->expires_at,
                        'current_time' => now()
                    ]);
                    $pendingOrder->markAsExpired();
                }

                // No valid pending order - try to extract from webhook metadata or create minimal order
                Log::warning('No valid pending order found for webhook payment', [
                    'reference' => $reference,
                    'webhook_data_keys' => array_keys($data),
                    'meta_data' => $data['meta'] ?? null,
                    'metadata' => $data['metadata'] ?? null
                ]);

                // Check if webhook contains custom metadata with order details
                $metadata = $data['meta'] ?? $data['metadata'] ?? [];

                if (isset($metadata['order_data'])) {
                    // Order data was passed in webhook metadata
                    $orderData = is_string($metadata['order_data'])
                        ? json_decode($metadata['order_data'], true)
                        : $metadata['order_data'];

                    Log::info('Found order data in webhook metadata', [
                        'order_data_keys' => array_keys($orderData)
                    ]);
                } else {
                    // Create minimal order data from available information
                    Log::info('Creating minimal order data from webhook');
                    $orderData = $this->createMinimalOrderData($reference, $data);
                }
            }

            if (!$orderData) {
                Log::error('Cannot create order - no data available', ['reference' => $reference]);
                return false;
            }

            // Get user - check multiple sources for user_id with improved logging
            $user = null;
            $userId = null;

            // Try to get user_id from various sources
            if (isset($orderData['user_id'])) {
                $userId = $orderData['user_id'];
                Log::info('Found user_id in order data', ['user_id' => $userId]);
            } elseif (isset($data['meta']['user_id'])) {
                $userId = $data['meta']['user_id'];
                Log::info('Found user_id in webhook meta', ['user_id' => $userId]);
            } elseif (isset($data['metadata']['user_id'])) {
                $userId = $data['metadata']['user_id'];
                Log::info('Found user_id in webhook metadata', ['user_id' => $userId]);
            }

            if ($userId) {
                $user = User::find($userId);
                Log::info('User lookup by ID', ['user_id' => $userId, 'user_found' => !empty($user)]);
            }

            if (!$user && isset($data['customer']['email'])) {
                $email = $data['customer']['email'];

                // Handle Flutterwave test emails that contain actual user email
                // Example: ravesb_857d0d5f33a080425a22_raufie384@gmail.com -> raufie384@gmail.com
                if (strpos($email, 'ravesb_') === 0 && strpos($email, '_') !== false) {
                    // Extract real email from Flutterwave test format
                    $parts = explode('_', $email);
                    if (count($parts) >= 3) {
                        $realEmail = end($parts); // Get the last part after final underscore
                        Log::info('Detected Flutterwave test email, extracting real email', [
                            'original_email' => $email,
                            'extracted_email' => $realEmail
                        ]);
                        $email = $realEmail;
                    }
                }

                $user = User::where('email', $email)->first();
                Log::info('User lookup by email', ['email' => $email, 'user_found' => !empty($user)]);

                // If still not found and it's a test environment, try to find by partial email match
                if (!$user && config('app.env') !== 'production') {
                    // Try to find user by partial email match (for development)
                    $user = User::where('email', 'like', '%' . $email . '%')
                        ->orWhere('email', 'like', '%raufie%')
                        ->first();
                    Log::info('Development mode: User lookup by partial email match', [
                        'email' => $email,
                        'user_found' => !empty($user),
                        'user_email' => $user->email ?? 'not_found'
                    ]);
                }
            }

            if (!$user) {
                Log::error('Cannot create order - user not found', [
                    'reference' => $reference,
                    'user_id' => $userId,
                    'orderData_user_id' => $orderData['user_id'] ?? null,
                    'meta_user_id' => $data['meta']['user_id'] ?? null,
                    'customer_email' => $data['customer']['email'] ?? null
                ]);

                // In development mode, create a fallback user to prevent webhook failures
                if (config('app.env') !== 'production' && isset($data['customer']['email'])) {
                    Log::info('Development mode: Creating fallback user for webhook');
                    $user = User::create([
                        'name' => $data['customer']['name'] ?? 'Webhook User',
                        'email' => $data['customer']['email'],
                        'phone' => $data['customer']['phone_number'] ?? '',
                        'email_verified_at' => now(),
                        'password' => bcrypt('temporary123'), // Temporary password
                    ]);
                    Log::info('Fallback user created', ['user_id' => $user->id, 'email' => $user->email]);
                } else {
                    return false;
                }
            }

            Log::info('Starting order creation transaction', [
                'user_id' => $user->id,
                'reference' => $reference
            ]);

            DB::beginTransaction();

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $orderData['subtotal'] ?? $data['amount'],
                'shipping_amount' => $orderData['delivery_charges'] ?? 0,
                'discount_amount' => $orderData['discount_amount'] ?? 0,
                'total_amount' => $orderData['total_amount'] ?? $data['amount'],
                'delivery_method' => $orderData['delivery_method'] ?? 'delivery',
                'delivery_address' => $orderData['delivery_address'] ?? '',
                'delivery_phone' => $orderData['delivery_phone'] ?? '',
                'delivery_instructions' => $orderData['delivery_instructions'] ?? '',
                'status' => 'pending', // Use 'pending' which is definitely valid, admin can change to confirmed/dispatched later
                'payment_status' => 'paid',
                'payment_reference' => $reference,
                'payment_method' => 'card',
            ]);

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

            // Create order items if available
            if (isset($orderData['order_items']) && is_array($orderData['order_items'])) {
                Log::info('Creating order items', [
                    'items_count' => count($orderData['order_items'])
                ]);

                foreach ($orderData['order_items'] as $item) {
                    $orderItem = $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                        'customizations' => $item['customizations'] ?? null,
                        'special_instructions' => $item['special_instructions'] ?? null,
                        'addon_total' => $item['addon_total'] ?? 0,
                    ]);

                    // Create addons
                    if (!empty($item['addons'])) {
                        Log::info('Creating addons for item', [
                            'order_item_id' => $orderItem->id,
                            'addons_count' => count($item['addons'])
                        ]);

                        foreach ($item['addons'] as $addon) {
                            $orderItem->addons()->create([
                                'product_addon_id' => $addon['addon_id'],
                                'quantity' => $addon['quantity'] ?? 1,
                                'price' => $addon['price'],
                                'total' => ($addon['price'] * ($addon['quantity'] ?? 1)),
                                'addon_type' => $addon['addon_type'] ?? 'general',
                                'name' => $addon['name'] ?? '',
                            ]);
                        }
                    }
                }
            } else {
                Log::warning('No order items found in order data', [
                    'reference' => $reference,
                    'order_data_structure' => array_keys($orderData)
                ]);
            }

            // Create payment record
            Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'transaction_id' => $data['id'] ?? null,
                'reference' => $reference,
                'amount' => $order->total_amount,
                'currency' => $data['currency'] ?? 'NGN',
                'payment_method' => 'card',
                'status' => 'successful',
                'gateway_response' => 'Payment successful via webhook - order auto-created',
                'gateway_data' => $data,
                'paid_at' => now(),
                'meta_data' => [
                    'order_number' => $order->order_number,
                    'created_via' => 'webhook',
                    'auto_created' => true
                ]
            ]);

            DB::commit();

            // Mark pending order as completed if it exists
            if ($pendingOrder) {
                $pendingOrder->markAsOrderCreated($order);
                Log::info('Pending order marked as completed', [
                    'pending_order_id' => $pendingOrder->id,
                    'order_id' => $order->id
                ]);
            }

            Log::info('Order created successfully from webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reference' => $reference,
                'transaction_id' => $data['id'] ?? null,
                'total_amount' => $order->total_amount,
                'pending_order_id' => $pendingOrder->id ?? null
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Webhook order creation failed: ' . $e->getMessage(), [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Create minimal order data when no cached data is available
     */
    private function createMinimalOrderData($reference, $data)
    {
        return [
            'subtotal' => $data['amount'] ?? 0,
            'delivery_charges' => 0,
            'discount_amount' => 0,
            'total_amount' => $data['amount'] ?? 0,
            'delivery_method' => 'delivery',
            'delivery_address' => 'Address not provided',
            'delivery_phone' => $data['customer']['phone_number'] ?? '',
            'delivery_instructions' => '',
            'order_items' => [], // Empty - will need manual admin intervention
            'created_via' => 'webhook_minimal',
            'note' => 'Order created from webhook payment - items need to be added manually'
        ];
    }

    /**
     * Verify webhook signature from Flutterwave
     */
    private function verifyWebhookSignature($payload, $signature)
    {
        $secretHash = config('services.flutterwave.secret_hash');

        if (!$secretHash) {
            Log::warning('Flutterwave secret hash not configured - skipping signature verification');
            return true; // Allow in development
        }

        if (!$signature) {
            Log::error('Missing webhook signature header (verif-hash)');
            return false;
        }

        // Flutterwave sends the secret hash directly in the verif-hash header
        // It should match exactly with your FLUTTERWAVE_SECRET_HASH
        if (!hash_equals($secretHash, $signature)) {
            Log::error('Webhook signature verification failed', [
                'expected_hash' => $secretHash,
                'received_hash' => $signature,
                'payload_reference' => $payload['data']['reference'] ?? 'unknown'
            ]);
            return false;
        }

        Log::info('Webhook signature verified successfully', [
            'hash_matched' => true,
            'reference' => $payload['data']['reference'] ?? 'unknown'
        ]);
        return true;
    }

    /**
     * Process refund completion webhook
     */
    private function processRefundWebhook($data)
    {
        try {
            Log::info('Processing refund webhook', [
                'refund_id' => $data['id'] ?? null,
                'amount' => $data['amount'] ?? null,
                'status' => $data['status'] ?? null,
                'full_data' => $data
            ]);

            // Flutterwave refund webhook might have different structure
            // Try multiple ways to find the refund record
            $refund = null;

            // Method 1: Find by Flutterwave refund transaction ID
            if (isset($data['id'])) {
                $refund = Refund::where('transaction_id', $data['id'])->first();
                Log::info('Refund search by transaction_id', [
                    'transaction_id' => $data['id'],
                    'found' => !empty($refund)
                ]);
            }

            // Method 2: Find by original transaction ID if refund contains it
            if (!$refund && isset($data['tx_id'])) {
                $refund = Refund::whereHas('payment', function ($query) use ($data) {
                    $query->where('transaction_id', $data['tx_id']);
                })->where('status', 'processing')->first();
                Log::info('Refund search by original tx_id', [
                    'tx_id' => $data['tx_id'],
                    'found' => !empty($refund)
                ]);
            }

            // Method 3: Find by amount and recent timestamp (last resort)
            if (!$refund && isset($data['amount'])) {
                $refund = Refund::where('amount', $data['amount'])
                    ->where('status', 'processing')
                    ->where('created_at', '>=', now()->subHours(1))
                    ->first();
                Log::info('Refund search by amount and time', [
                    'amount' => $data['amount'],
                    'found' => !empty($refund)
                ]);
            }

            if ($refund) {
                // Update the refund transaction_id if it was null
                if (empty($refund->transaction_id) && isset($data['id'])) {
                    $refund->update(['transaction_id' => $data['id']]);
                    Log::info('Updated refund transaction_id', [
                        'refund_id' => $refund->id,
                        'transaction_id' => $data['id']
                    ]);
                }

                if (isset($data['status']) && $data['status'] === 'completed') {
                    $refund->markAsSuccessful($data);
                    Log::info('Refund marked as successful via webhook', ['refund_id' => $refund->id]);
                } elseif (isset($data['status']) && in_array($data['status'], ['failed', 'error'])) {
                    $refund->markAsFailed('Refund failed via webhook: ' . ($data['message'] ?? 'Unknown error'), $data);
                    Log::info('Refund marked as failed via webhook', ['refund_id' => $refund->id]);
                } else {
                    Log::info('Refund webhook with unhandled status', [
                        'refund_id' => $refund->id,
                        'status' => $data['status'] ?? 'unknown'
                    ]);
                }
                return true;
            }

            Log::warning('Refund not found for webhook', ['data' => $data]);
            return false;
        } catch (\Exception $e) {
            Log::error('Refund webhook processing error: ' . $e->getMessage(), [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
