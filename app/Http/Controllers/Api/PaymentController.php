<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\FlutterwavePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(FlutterwavePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Webhook handler for Flutterwave events
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('Verif-Hash');
            $result = $this->paymentService->handleWebhook($payload, $signature);

            if ($result) {
                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['status' => 'failed'], 400);
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Request $request, $order_id): JsonResponse
    {
        $user = Auth::user();
        $order = Order::where('id', $order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found or access denied'
            ], 404);
        }

        if (!$order->payment_reference) {
            return response()->json([
                'error' => true,
                'message' => 'No payment reference found for this order'
            ], 400);
        }

        $status = $this->paymentService->getPaymentStatus($order->payment_reference);

        // If Flutterwave can't find the transaction, return the error
        if (isset($status['status']) && $status['status'] === 'error') {
            return response()->json([
                'error' => true,
                'message' => 'Payment verification failed: ' . ($status['message'] ?? 'Unknown error'),
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                    'order_status' => $order->status,
                    'payment_reference' => $order->payment_reference,
                    'note' => 'This is a known issue with Flutterwave sandbox timing. In production, webhooks will handle this automatically.'
                ]
            ], 400);
        }

        return response()->json([
            'error' => false,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'order_id' => $order->id,
                'payment_status' => $status,
                'order_status' => $order->status,
                'payment_reference' => $order->payment_reference,
            ]
        ]);
    }

    /**
     * Refund a payment
     */
    public function refund(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found or access denied'
            ], 404);
        }

        if (!$order->payment_reference) {
            return response()->json([
                'error' => true,
                'message' => 'No payment reference found for this order'
            ], 400);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'error' => true,
                'message' => 'Only paid orders can be refunded'
            ], 400);
        }

        $result = $this->paymentService->processRefund(
            $order->payment_reference,
            $request->amount,
            $request->reason ?? 'Customer request'
        );

        if ($result['success']) {
            return response()->json([
                'error' => false,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Complete card payment with PIN or AVS authorization
     * Unified API that handles both authorization types
     */
    public function completeCardPayment(Request $request): JsonResponse
    {
        // Base validation rules
        $rules = [
            'card_number' => 'required|string',
            'cvv' => 'required|string',
            'expiry_month' => 'required|string',
            'expiry_year' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|string',
            'email' => 'required|email',
            'fullname' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'tx_ref' => 'required|string',
            'redirect_url' => 'nullable|string',
            'authorization' => 'required|array',
            'authorization.mode' => 'required|string|in:pin,avs',
        ];

        // Add conditional validation based on authorization mode
        $authMode = $request->input('authorization.mode');

        if ($authMode === 'pin') {
            $rules['authorization.pin'] = 'required|string';
        } elseif ($authMode === 'avs') {
            $rules['authorization.city'] = 'required|string';
            $rules['authorization.address'] = 'required|string';
            $rules['authorization.state'] = 'required|string';
            $rules['authorization.country'] = 'required|string';
            $rules['authorization.zipcode'] = 'required|string';
        }

        $request->validate($rules);

        // Structure the data according to Flutterwave API format
        $cardData = [
            'card_number' => $request->card_number,
            'cvv' => $request->cvv,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'email' => $request->email,
            'fullname' => $request->fullname,
            'phone_number' => $request->phone_number,
            'tx_ref' => $request->tx_ref,
            'redirect_url' => $request->redirect_url ?? 'https://example.com',
            'authorization' => $request->authorization
        ];

        $result = $this->paymentService->chargeCard($cardData);

        if ($result['success']) {
            // Find and update the order
            $order = Order::where('payment_reference', $request->tx_ref)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                ]);
            }

            return response()->json([
                'error' => false,
                'message' => $result['message'],
                'data' => [
                    'order' => $order ? new OrderResource($order) : null,
                    'payment_data' => $result['data'],
                ]
            ], 200);
        }

        return response()->json([
            'error' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ], 400);
    }

    /**
     * Complete card payment with PIN
     * @deprecated Use completeCardPayment instead
     */
    public function completeCardPaymentWithPin(Request $request): JsonResponse
    {
        $request->validate([
            'card_number' => 'required|string',
            'cvv' => 'required|string',
            'expiry_month' => 'required|string',
            'expiry_year' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|string',
            'email' => 'required|email',
            'fullname' => 'nullable|string',
            'phone_number' => 'required|string',
            'tx_ref' => 'required|string',
            'authorization' => 'required|array',
            'authorization.mode' => 'required|string',
            'authorization.pin' => 'required|string',
        ]);

        // Structure the data according to Flutterwave API format
        $cardData = [
            'card_number' => $request->card_number,
            'cvv' => $request->cvv,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'email' => $request->email,
            'fullname' => $request->fullname,
            'phone_number' => $request->phone_number,
            'tx_ref' => $request->tx_ref,
            'authorization' => [
                'mode' => $request->authorization['mode'],
                'pin' => $request->authorization['pin']
            ]
        ];

        $result = $this->paymentService->chargeCard($cardData);

        if ($result['success']) {
            // Find and update the order
            $order = Order::where('payment_reference', $request->tx_ref)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                ]);
            }

            return response()->json([
                'error' => false,
                'message' => $result['message'],
                'data' => [
                    'order' => $order ? new OrderResource($order) : null,
                    'payment_data' => $result['data'],
                ]
            ], 200);
        } elseif (isset($result['requires_verification']) && $result['requires_verification']) {
            // Payment requires further verification (OTP)
            return response()->json([
                'error' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'requires_verification' => true
            ], 400);
        }

        return response()->json([
            'error' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ], 400);
    }
    /**
     * Complete card payment with AVS data
     */
    public function completeCardPaymentWithAvs(Request $request): JsonResponse
    {
        $request->validate([
            'card_number' => 'required|string',
            'cvv' => 'required|string',
            'expiry_month' => 'required|string',
            'expiry_year' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|string',
            'email' => 'required|email',
            'fullname' => 'required|string',
            'phone_number' => 'required|string',
            'tx_ref' => 'required|string',
            'authorization' => 'required|array',
            'authorization.mode' => 'required|string',
            'authorization.city' => 'required|string',
            'authorization.address' => 'required|string',
            'authorization.state' => 'required|string',
            'authorization.country' => 'required|string',
            'authorization.zipcode' => 'required|string',
        ]);

        // Structure the data according to Flutterwave API format
        $cardData = [
            'card_number' => $request->card_number,
            'cvv' => $request->cvv,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'email' => $request->email,
            'fullname' => $request->fullname,
            'phone_number' => $request->phone_number,
            'tx_ref' => $request->tx_ref,
            'authorization' => [
                'mode' => $request->authorization['mode'],
                'city' => $request->authorization['city'],
                'address' => $request->authorization['address'],
                'state' => $request->authorization['state'],
                'country' => $request->authorization['country'],
                'zipcode' => $request->authorization['zipcode']
            ]
        ];

        $result = $this->paymentService->chargeCard($cardData);

        if ($result['success']) {
            // Find and update the order
            $order = Order::where('payment_reference', $request->tx_ref)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                ]);
            }

            return response()->json([
                'error' => false,
                'message' => $result['message'],
                'data' => [
                    'order' => $order ? new OrderResource($order) : null,
                    'payment_data' => $result['data'],
                ]
            ], 200);
        }

        return response()->json([
            'error' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ], 400);
    }

    /**
     * Check payment reference validity before OTP verification
     */
    public function checkPaymentReference(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
            'flw_ref' => 'required|string',
        ]);

        try {
            // Try to get transaction status first to check if reference is valid
            $status = $this->paymentService->verifyTransaction($request->flw_ref);

            if ($status && isset($status['status']) && $status['status'] === 'success') {
                $data = $status['data'];

                return response()->json([
                    'error' => false,
                    'message' => 'Payment reference is valid',
                    'data' => [
                        'reference' => $request->reference,
                        'flw_ref' => $request->flw_ref,
                        'transaction_status' => $data['status'] ?? 'unknown',
                        'can_verify_otp' => in_array($data['status'] ?? '', ['pending_otp', 'pending']),
                        'payment_info' => [
                            'amount' => $data['amount'] ?? null,
                            'currency' => $data['currency'] ?? 'NGN',
                            'status' => $data['status'] ?? 'unknown'
                        ]
                    ]
                ], 200);
            }

            return response()->json([
                'error' => true,
                'message' => 'Payment reference is invalid or expired',
                'data' => [
                    'reference' => $request->reference,
                    'flw_ref' => $request->flw_ref,
                    'suggestion' => 'Please initiate a new payment transaction'
                ]
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Unable to verify payment reference: ' . $e->getMessage(),
                'data' => [
                    'reference' => $request->reference,
                    'flw_ref' => $request->flw_ref,
                ]
            ], 400);
        }
    }

    /**
     * Unified payment verification for OTP and 3D Secure
     * Handles both OTP validation and 3D Secure verification
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
            'verification_type' => 'required|string|in:otp,3dsecure',
            'transaction_id' => 'required_if:verification_type,3dsecure', // transaction_id for 3DS
            'flw_ref' => 'required_if:verification_type,otp|string', // flw_ref for OTP
            'otp' => 'required_if:verification_type,otp|string', // Only required for OTP
        ]);

        $verificationType = $request->verification_type;
        $transactionId = $request->transaction_id;
        $flwRef = $request->flw_ref;
        $reference = $request->reference;

        // Different verification methods based on type
        if ($verificationType === 'otp') {
            $result = $this->paymentService->validateCharge(
                $request->otp,
                $flwRef
            );
        } else { // 3dsecure
            $result = $this->paymentService->verifyTransaction($transactionId);
        }

        // Common logic for both verification types
        if ($result && ((isset($result['success']) && $result['success']) ||
            (isset($result['status']) && $result['status'] === 'success'))) {

            // Handle different result structures
            $data = $result['data'] ?? $result;

            // Check if payment is successful
            $isSuccessful = false;
            if (isset($data['status']) && in_array($data['status'], ['successful', 'success'])) {
                $isSuccessful = true;
            } elseif (isset($result['success']) && $result['success']) {
                $isSuccessful = true;
            }

            if ($isSuccessful) {
                // Find the order by payment reference
                $order = Order::where('payment_reference', $reference)->first();

                if (!$order) {
                    // ✅ Payment verified successfully
                    // ❌ DO NOT create order here - let webhook handle it
                    // This ensures proper webhook-based order creation flow

                    return response()->json([
                        'error' => false,
                        'message' => 'Payment verified successfully',
                        'data' => [
                            'payment_data' => [
                                'status' => 'successful',
                                'reference' => $reference,
                                'transaction_id' => $data['id'] ?? $data['transaction_id'] ?? ($verificationType === 'otp' ? $flwRef : $transactionId),
                                'amount' => $data['amount'] ?? null,
                                'currency' => $data['currency'] ?? 'NGN',
                                'payment_type' => 'card'
                            ],
                        ]
                    ], 200);
                } else {
                    // Order already exists (created by webhook), just update payment status
                    $order->update([
                        'payment_status' => 'paid',
                    ]);

                    // Create/update payment record
                    Payment::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'user_id' => $order->user_id,
                            'transaction_id' => $data['id'] ?? $data['transaction_id'] ?? ($verificationType === 'otp' ? $flwRef : $transactionId),
                            'reference' => $reference,
                            'amount' => $order->total_amount,
                            'currency' => 'NGN',
                            'payment_method' => 'card',
                            'status' => 'successful',
                            'gateway_response' => $verificationType === 'otp'
                                ? 'Payment successful via OTP verification'
                                : 'Payment successful via 3D Secure verification',
                            'gateway_data' => $data ?? [],
                            'paid_at' => now(),
                        ]
                    );

                    return response()->json([
                        'error' => false,
                        'message' => 'Payment verified successfully',
                        'data' => [
                            'payment_data' => [
                                'status' => 'successful',
                                'reference' => $reference,
                                'transaction_id' => $data['id'] ?? $data['transaction_id'] ?? ($verificationType === 'otp' ? $flwRef : $transactionId),
                                'amount' => $data['amount'] ?? null,
                                'currency' => $data['currency'] ?? 'NGN',
                                'payment_type' => 'card'
                            ]
                        ]
                    ], 200);
                }
            }
        }

        // Handle specific Flutterwave errors with better messaging
        $errorMessage = 'Payment verification failed or payment not successful';
        $isFlutterwaveError = false;

        if (isset($result['message']) && str_contains($result['message'], 'No REF Cache')) {
            $errorMessage = 'Payment verification failed: The payment session has expired. This is common in Flutterwave sandbox mode.';
            $isFlutterwaveError = true;
            $suggestion = 'Please initiate a new payment transaction. In sandbox mode, payment sessions expire quickly (usually within 5-10 minutes).';
        } elseif (isset($result['message']) && str_contains($result['message'], 'Invalid transaction attempt')) {
            $errorMessage = 'Payment verification failed: Invalid transaction attempt. The payment session may have expired.';
            $isFlutterwaveError = true;
            $suggestion = 'Please try again with a new payment. Ensure you complete the OTP verification quickly after receiving it.';
        } elseif (isset($result['data']['status']) && $result['data']['status'] === 'error') {
            $errorMessage = 'Payment verification failed: ' . ($result['data']['message'] ?? 'Unknown Flutterwave error');
            $isFlutterwaveError = true;
            $suggestion = 'Please contact support if this issue persists.';
        }

        // Log the error for debugging
        Log::warning('Payment verification failed', [
            'verification_type' => $verificationType,
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'flw_ref' => $flwRef,
            'error_message' => $errorMessage,
            'full_result' => $result
        ]);

        return response()->json([
            'error' => true,
            'message' => $errorMessage,
            'data' => [
                'verification_type' => $verificationType,
                'reference' => $reference,
                'transaction_id' => $transactionId,
                'flw_ref' => $flwRef,
                'is_flutterwave_error' => $isFlutterwaveError,
                'verification_result' => $result,
            ]
        ], 400);
    }

    /**
     * Automatically create order from payment verification using cached order data
     */
    protected function autoCreateOrderFromPayment($reference, $paymentData, $verificationType)
    {
        try {
            // Try to get order data from PendingOrder table instead of cache
            $pendingOrder = \App\Models\PendingOrder::where('payment_reference', $reference)
                ->where('status', 'pending')
                ->first();

            if (!$pendingOrder) {
                return [
                    'success' => false,
                    'message' => 'Pending order not found for this payment reference. Please retry order creation manually.'
                ];
            }

            // Check if pending order has expired
            if ($pendingOrder->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'Pending order has expired. Please create a new order.'
                ];
            }

            // Get order data from the pending order
            $orderData = $pendingOrder->order_data;
            $user = $pendingOrder->user;

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found for order creation.'
                ];
            }

            DB::beginTransaction();

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $orderData['subtotal'],
                'shipping_amount' => $orderData['delivery_charges'] ?? 0,
                'discount_amount' => $orderData['discount_amount'] ?? 0,
                'total_amount' => $orderData['total_amount'],
                'delivery_method' => $orderData['delivery_method'] ?? 'delivery',
                'delivery_address' => $orderData['delivery_address'] ?? '',
                'delivery_phone' => $orderData['delivery_phone'] ?? '',
                'delivery_instructions' => $orderData['delivery_instructions'] ?? '',
                'status' => 'pending', // Use 'pending' which is definitely valid, admin can change to confirmed/dispatched later
                'payment_status' => 'paid',
                'payment_reference' => $reference,
                'payment_method' => 'card',
            ]);

            // Create order items
            if (isset($orderData['order_items']) && is_array($orderData['order_items'])) {
                foreach ($orderData['order_items'] as $item) {
                    $orderItem = $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                        'customizations' => $item['customizations'] ?? null,
                        'special_instructions' => $item['special_instructions'] ?? null,
                    ]);

                    // Create addons if they exist
                    if (!empty($item['addons'])) {
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
            }

            // Create payment record
            Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'transaction_id' => $paymentData['id'] ?? $paymentData['transaction_id'] ?? null,
                'reference' => $reference,
                'amount' => $order->total_amount,
                'currency' => $paymentData['currency'] ?? 'NGN',
                'payment_method' => 'card',
                'status' => 'successful',
                'gateway_response' => $verificationType === 'otp'
                    ? 'Payment successful via OTP verification with auto order creation'
                    : 'Payment successful via 3D Secure verification with auto order creation',
                'gateway_data' => $paymentData ?? [],
                'paid_at' => now(),
                'meta_data' => [
                    'order_number' => $order->order_number,
                    'payment_type' => 'auto_created_after_verification',
                    'verification_type' => $verificationType,
                    'auto_created' => true
                ]
            ]);

            // Update pending order status
            $pendingOrder->update([
                'status' => 'completed',
                'order_id' => $order->id,
                'order_created_at' => now(),
                'payment_verified_at' => now(),
                'notes' => "Order created automatically after {$verificationType} verification"
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Order created successfully after payment verification',
                'order' => new OrderResource($order)
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Auto order creation failed: ' . $e->getMessage(), [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Auto order creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * @deprecated Use verifyPayment with verification_type=otp instead
     */
    public function validateOtp(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
            'otp' => 'required|string',
            'flw_ref' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        // Use flw_ref if available, otherwise use transaction_id
        $flwRef = $request->flw_ref ?? $request->transaction_id;

        if (!$flwRef) {
            return response()->json([
                'error' => true,
                'message' => 'Either flw_ref or transaction_id is required for OTP validation'
            ], 400);
        }

        $result = $this->paymentService->validateCharge(
            $request->otp,
            $flwRef
        );

        if ($result['success']) {
            // Find the order by payment reference
            $order = Order::where('payment_reference', $request->reference)->first();

            if (!$order) {
                // Order doesn't exist, this means it was a pending payment
                // We need to create the order now that payment is successful
                return response()->json([
                    'error' => false,
                    'message' => 'Payment validated successfully. Order creation is pending.',
                    'data' => [
                        'payment_validated' => true,
                        'payment_data' => $result['data'],
                        'next_step' => 'You need to call the order creation API again with the same data to complete the order',
                        'note' => 'The payment has been processed successfully. Please retry your original order creation request.'
                    ]
                ]);
            } else {
                // Order exists, update it
                $order->update([
                    'payment_status' => 'paid',
                ]);

                // Create payment record
                Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'user_id' => $order->user_id,
                        'transaction_id' => $result['data']['transaction_id'] ?? null,
                        'reference' => $request->reference,
                        'amount' => $order->total_amount,
                        'currency' => 'NGN',
                        'payment_method' => 'card',
                        'status' => 'successful',
                        'gateway_response' => 'Payment successful via OTP validation',
                        'gateway_data' => $result['data'] ?? [],
                        'paid_at' => now(),
                    ]
                );

                return response()->json([
                    'error' => false,
                    'message' => $result['message'],
                    'data' => [
                        'order' => new OrderResource($order),
                        'payment_data' => $result['data'],
                    ]
                ]);
            }
        }

        return response()->json([
            'error' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ], 400);
    }

    /**
     * Verify 3D Secure transaction by transaction ID
     */
    public function verify3DSecure(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'reference' => 'required|string',
        ]);

        $result = $this->paymentService->verifyTransaction($request->transaction_id);

        if ($result && isset($result['status']) && $result['status'] === 'success') {
            $data = $result['data'];

            if (isset($data['status']) && $data['status'] === 'successful') {
                // Find the order by payment reference
                $order = Order::where('payment_reference', $request->reference)->first();

                if (!$order) {
                    // Order doesn't exist, payment successful but order creation pending
                    return response()->json([
                        'error' => false,
                        'message' => 'Payment verified successfully. Order creation is pending.',
                        'data' => [
                            'payment_validated' => true,
                            'payment_data' => [
                                'status' => 'successful',
                                'reference' => $request->reference,
                                'transaction_id' => $data['id'] ?? null,
                                'amount' => $data['amount'] ?? null,
                                'currency' => $data['currency'] ?? 'NGN',
                                'payment_type' => 'card'
                            ],
                        ]
                    ]);
                } else {
                    // Order exists, update it
                    $order->update([
                        'payment_status' => 'paid',
                    ]);

                    // Create payment record
                    Payment::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'user_id' => $order->user_id,
                            'transaction_id' => $data['id'] ?? null,
                            'reference' => $request->reference,
                            'amount' => $order->total_amount,
                            'currency' => 'NGN',
                            'payment_method' => 'card',
                            'status' => 'successful',
                            'gateway_response' => 'Payment successful via 3D Secure verification',
                            'gateway_data' => $data ?? [],
                            'paid_at' => now(),
                        ]
                    );

                    return response()->json([
                        'error' => false,
                        'message' => 'Payment verified successfully',
                        'data' => [
                            'order' => new OrderResource($order),
                            'payment_data' => [
                                'status' => 'successful',
                                'reference' => $request->reference,
                                'transaction_id' => $data['id'] ?? null,
                                'amount' => $data['amount'] ?? null,
                                'currency' => $data['currency'] ?? 'NGN',
                                'payment_type' => 'card'
                            ]
                        ]
                    ]);
                }
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'Payment verification failed or payment not successful',
            'data' => [
                'reference' => $request->reference,
                'transaction_id' => $request->transaction_id,
                'verification_result' => $result
            ]
        ], 400);
    }
}
