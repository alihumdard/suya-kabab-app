<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\AddonCategory;
use App\Models\CardPayload;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PendingOrder;
use App\Services\FlutterwavePaymentService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\DiscountCode;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $paymentService;

    public function __construct(FlutterwavePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display user's orders.
     */
    public function index(Request $request)
    {
        $orders = Order::with(['items.product.images'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'error' => false,
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ]
            ]
        ]);
    }

    /**
     * Create a new order with payment-first approach.
     */
    public function store(CreateOrderRequest $request)
    {
        $user = $request->user();

        // Validate the request
        $validated = $request->validated();

        try {
            // Step 1: Validate and prepare order data (without creating order)
            $orderData = $this->prepareOrderData($request, $user);

            // Step 2: Process payment first (for card payments)
            $paymentMethod = $request->payment_method ?? 'cod';

            if ($paymentMethod === 'card') {
                $paymentResult = $this->processCardPayment($request, $orderData['total_amount'], $user, $orderData);

                if ($paymentResult['success']) {
                    // Payment successful - create order and commit
                    return $this->createOrderAfterPayment($orderData, $paymentResult, $user, $request);
                } elseif (isset($paymentResult['requires_verification']) && $paymentResult['requires_verification']) {
                    // Payment requires verification - create pending order for later auto-creation
                    $paymentReference = $paymentResult['data']['reference'] ?? null;

                    if ($paymentReference) {
                        // Prepare order data for pending order
                        $orderDataForPending = array_merge($orderData, [
                            'user_id' => $user->id,
                            'delivery_method' => $request->delivery_method,
                            'delivery_address' => $request->delivery_address,
                            'delivery_phone' => $request->delivery_phone,
                            'delivery_instructions' => $request->delivery_instructions,
                            'payment_method' => $paymentMethod,
                            'created_at' => now()->toISOString()
                        ]);

                        // Create pending order (expires in 2 hours)
                        $pendingOrder = PendingOrder::createForPayment(
                            $orderDataForPending,
                            $paymentReference,
                            $user->id,
                            2 // expires in 2 hours
                        );

                        Log::info('Pending order created for payment verification', [
                            'reference' => $paymentReference,
                            'pending_order_id' => $pendingOrder->id,
                            'user_id' => $user->id,
                            'expires_at' => $pendingOrder->expires_at
                        ]);
                    }

                    return response()->json([
                        'error' => true,
                        'message' => $paymentResult['message'],
                        'payment_details' => $paymentResult['data'] ?? null
                    ], 400);
                } else {
                    // Payment failed - return error
                    return response()->json([
                        'error' => true,
                        'message' => $paymentResult['message'],
                        'payment_details' => $paymentResult['data'] ?? null
                    ], 400);
                }
            } else {
                // For non-card payments (COD, cash), create order directly
                return $this->createOrderForNonCardPayment($orderData, $paymentMethod, $user, $request);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Prepare order data without creating the order
     */
    protected function prepareOrderData(Request $request, $user)
    {
        $subtotal = 0;
        $addonTotal = 0;
        $orderItems = [];

        // Validate order items and calculate total
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);

            if ($product->status !== 'active') {
                throw new \Exception("Product {$product->name} is not available");
            }

            if (!$product->isInStock()) {
                throw new \Exception("Product {$product->name} is out of stock");
            }

            if ($product->track_quantity && $product->quantity < $item['quantity']) {
                throw new \Exception("Insufficient stock for {$product->name}");
            }

            // Calculate item total
            $itemTotal = $product->price * $item['quantity'];

            // Process add-ons using the dedicated method
            $addonResult = $this->processAddons($item);
            $itemAddons = $addonResult['addons'];
            $itemAddonTotal = $addonResult['addon_total'];

            // If addon_total was provided, use it (backward compatibility)
            if (isset($item['addon_total'])) {
                $itemAddonTotal = $item['addon_total'];
            }

            $subtotal += $itemTotal;
            $addonTotal += $itemAddonTotal;

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'total' => $itemTotal,
                'customizations' => $item['customizations'] ?? null,
                'special_instructions' => $item['special_instructions'] ?? null,
                'addon_total' => $itemAddonTotal,
                'addons' => !empty($itemAddons) ? $itemAddons : null,
            ];
        }

        // Add addon total to subtotal
        $subtotal += $addonTotal;

        // Calculate delivery charges dynamically
        $deliveryCharges = Setting::calculateDeliveryCharges($subtotal, $request->delivery_method);

        // Calculate discount
        $discountAmount = 0;
        $discountCode = null;
        if ($request->discount_code) {
            $discountCode = DiscountCode::where('code', $request->discount_code)->first();
            if ($discountCode && $discountCode->isValid($subtotal)) {
                $discountAmount = $discountCode->calculateDiscount($subtotal);
            }
        }

        // Calculate total amount
        $totalAmount = $subtotal + $deliveryCharges - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'addon_total' => $addonTotal,
            'delivery_charges' => $deliveryCharges,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'order_items' => $orderItems,
            'discount_code' => $discountCode,
            'request_data' => $request->all()
        ];
    }

    /**
     * Create order after successful payment
     */
    protected function createOrderAfterPayment($orderData, $paymentResult, $user, $request)
    {
        DB::beginTransaction();

        try {
            // Create order with pending status (as per migration)
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $orderData['subtotal'],
                'shipping_amount' => $orderData['delivery_charges'],
                'discount_amount' => $orderData['discount_amount'],
                'total_amount' => $orderData['total_amount'],
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_address,
                'delivery_phone' => $request->delivery_phone,
                'delivery_instructions' => $request->delivery_instructions,
                'status' => 'pending',
                'payment_status' => 'paid',
                'payment_reference' => $paymentResult['data']['reference'] ?? null,
                'payment_method' => 'card',
            ]);

            // Create payment record
            $cardDetails = $request->card_details ?? [];
            $paymentRecord = Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'transaction_id' => $paymentResult['data']['transaction_id'] ?? null,
                'reference' => $paymentResult['data']['reference'] ?? $order->order_number,
                'amount' => $orderData['total_amount'],
                'currency' => 'NGN',
                'payment_method' => 'card',
                'status' => 'successful',
                'gateway_response' => 'Payment successful',
                'gateway_data' => $paymentResult['data'] ?? [],
                'card_last4' => substr($cardDetails['card_number'] ?? '', -4),
                'card_brand' => $this->getCardBrand($cardDetails['card_number'] ?? ''),
                'card_holder_name' => $cardDetails['card_holder_name'] ?? '',
                'payment_channel' => 'card',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'paid_at' => now(),
                'meta_data' => [
                    'order_number' => $order->order_number,
                    'payment_type' => 'direct_card_payment'
                ]
            ]);

            // Create order items
            foreach ($orderData['order_items'] as $index => $item) {
                $orderItem = $order->items()->create($item);

                // Save addons
                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        $orderItem->addons()->create([
                            'product_addon_id' => $addon['addon_id'],
                            'quantity' => $addon['quantity'] ?? 1,
                            'price' => $addon['price']
                        ]);
                    }
                }
            }

            // Update product quantities (only after payment success)
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->track_quantity) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            // Mark discount code as used
            if ($orderData['discount_code'] && $orderData['discount_amount'] > 0) {
                $orderData['discount_code']->markAsUsed();
            }

            DB::commit();

            // Load relationships
            $order->load([
                'items' => function ($query) {
                    $query->with([
                        'product.images',
                        'addons' => function ($query) {
                            $query->with(['productAddon.category']);
                        }
                    ]);
                }
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Order created and payment processed successfully',
                'data' => new OrderResource($order),
                'payment_details' => [
                    'transaction_id' => $paymentResult['data']['transaction_id'] ?? null,
                    'reference' => $paymentResult['data']['reference'] ?? null,
                    'status' => $paymentResult['data']['status'] ?? null,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create order for non-card payments (COD, cash)
     */
    protected function createOrderForNonCardPayment($orderData, $paymentMethod, $user, $request)
    {
        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $orderData['subtotal'],
                'shipping_amount' => $orderData['delivery_charges'],
                'discount_amount' => $orderData['discount_amount'],
                'total_amount' => $orderData['total_amount'],
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_address,
                'delivery_phone' => $request->delivery_phone,
                'delivery_instructions' => $request->delivery_instructions,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod === 'cod' ? 'cash' : $paymentMethod, // Map 'cod' to 'cash' for database enum
            ]);

            // Create payment record
            $paymentRecord = Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'reference' => $order->order_number,
                'amount' => $orderData['total_amount'],
                'currency' => 'NGN',
                'payment_method' => $paymentMethod === 'cod' ? 'cash' : $paymentMethod, // Map 'cod' to 'cash' for database enum
                'status' => 'pending',
                'gateway_response' => $paymentMethod === 'cod' ? 'Cash on delivery' : 'Cash payment',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta_data' => [
                    'order_number' => $order->order_number,
                    'payment_type' => $paymentMethod === 'cod' ? 'cash_on_delivery' : 'cash_payment',
                    'original_payment_method' => $paymentMethod // Keep track of original method
                ]
            ]);

            // Create order items
            foreach ($orderData['order_items'] as $index => $item) {
                $orderItem = $order->items()->create($item);

                // Save addons
                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        $orderItem->addons()->create([
                            'product_addon_id' => $addon['addon_id'],
                            'quantity' => $addon['quantity'] ?? 1,
                            'price' => $addon['price']
                        ]);
                    }
                }
            }

            // Update product quantities
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->track_quantity) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }


            // Track discount code usage
            if ($request->filled('discount_code')) {
                $discount = DiscountCode::where('code', $request->discount_code)->first();
                if (!$discount) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid discount code'
                    ], 400);
                }

                // Check if user has already used this discount code
                $alreadyUsed = DB::table('discount_code_user')
                    ->where('discount_code_id', $discount->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($alreadyUsed) {
                    return response()->json([
                        'error' => true,
                        'message' => 'You have already used this discount code.'
                    ], 400);
                }

                // Check if the discount code has reached its usage limit
                if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
                    return response()->json([
                        'error' => true,
                        'message' => 'This discount code has reached its maximum usage limit.'
                    ], 400);
                }

                // Increment global usage count
                $discount->increment('used_count');

                // Save user usage record
                DB::table('discount_code_user')->insert([
                    'discount_code_id' => $discount->id,
                    'user_id' => $user->id,
                    'used_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            // Load relationships
            $order->load([
                'items' => function ($query) {
                    $query->with([
                        'product.images',
                        'addons' => function ($query) {
                            $query->with(['productAddon.category']);
                        }
                    ]);
                }
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order)
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Store card payload data
     */
    protected function storeCardPayload(Request $request, $user)
    {
        try {
            $cardDetails = $request->card_details;

            $cardPayload = CardPayload::create([
                'card_number' => $cardDetails['card_number'],
                'expiry_month' => $cardDetails['expiry_month'],
                'expiry_year' => $cardDetails['expiry_year'],
                'cvv' => $cardDetails['cvv'], // Note: In production, this should be encrypted or not stored
                'card_holder_name' => $cardDetails['card_holder_name'],
                'email' => $user->email,
                'currency' => 'NGN',
                'user_id' => $user->id,
            ]);

            Log::info('Card payload stored successfully', [
                'card_payload_id' => $cardPayload->id,
                'user_id' => $user->id,
                'card_last_four' => substr($cardDetails['card_number'], -4)
            ]);

            return $cardPayload;
        } catch (\Exception $e) {
            Log::error('Failed to store card payload: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            // Don't throw exception here as we don't want to fail the order
            // just because card payload storage failed
            return null;
        }
    }

    /**
     * Process card payment using Flutterwave
     */
    protected function processCardPayment(Request $request, $amount, $user, $orderData = null)
    {
        try {
            // First, store the card payload data
            $cardPayload = $this->storeCardPayload($request, $user);

            $cardDetails = $request->card_details;

            $cardData = [
                'card_number' => $cardDetails['card_number'],
                'cvv' => $cardDetails['cvv'],
                'expiry_month' => (int) $cardDetails['expiry_month'], // Convert to integer
                'expiry_year' => (int) $cardDetails['expiry_year'], // Convert to integer
                'currency' => 'NGN',
                'amount' => (int) $amount, // Convert to integer as required by Flutterwave
                'email' => $user->email,
                'phone_number' => $user->phone,
                'fullname' => $cardDetails['card_holder_name'],
                'tx_ref' => 'ORDER_' . time() . '_' . $user->id,
                'redirect_url' => config('app.frontend_url') . '/payment/callback',
                'meta' => [
                    'user_id' => $user->id,
                    'payment_type' => 'card_payment',
                    // Include order data for webhook processing
                    'order_data' => $orderData ? json_encode([
                        'user_id' => $user->id,
                        'subtotal' => $orderData['subtotal'],
                        'delivery_charges' => $orderData['delivery_charges'],
                        'discount_amount' => $orderData['discount_amount'],
                        'total_amount' => $orderData['total_amount'],
                        'order_items' => $orderData['order_items'],
                        'delivery_method' => $request->delivery_method,
                        'delivery_address' => $request->delivery_address,
                        'delivery_phone' => $request->delivery_phone,
                        'delivery_instructions' => $request->delivery_instructions,
                        'discount_code' => $request->discount_code,
                        'created_via' => 'webhook'
                    ]) : null
                ]
            ];

            return $this->paymentService->chargeCard($cardData);
        } catch (\Exception $e) {
            Log::error('Card payment processing error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process addons for an order item
     *
     * @param array $item The order item data
     * @return array
     */
    protected function processAddons(array $item): array
    {
        $baseFields = ['product_id', 'quantity', 'customizations', 'special_instructions', 'addon_total'];
        $addonFields = array_diff(array_keys($item), $baseFields);

        $addons = [];
        $addonTotal = 0;


        foreach ($addonFields as $addonType) {
            // Skip if the addon type is not an array
            if (!is_array($item[$addonType] ?? null)) {
                continue;
            }

            // Log the addon type being processed

            // First try to find by exact slug or name match
            $category = AddonCategory::where('slug', $addonType)
                ->orWhere('name', $addonType)
                ->first();

            // If not found, try case-insensitive match
            if (!$category) {
                $category = AddonCategory::whereRaw('LOWER(slug) = ?', [strtolower($addonType)])
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($addonType)])
                    ->first();
            }

            // If still not found, try to match with singular/plural forms
            if (!$category) {
                $singular = Str::singular($addonType);
                $plural = Str::plural($addonType);

                if ($singular !== $addonType || $plural !== $addonType) {
                    $category = AddonCategory::whereIn('slug', [$singular, $plural])
                        ->orWhereIn('name', [$singular, $plural])
                        ->first();
                }
            }

            if (!$category) {
                Log::warning('Category not found', ['addon_type' => $addonType]);
                continue; // Skip unknown category
            }



            // Get unique addon IDs from the request
            $addonIds = collect($item[$addonType])
                ->pluck('id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();



            if (empty($addonIds)) {
                continue;
            }

            // Get only addons that exist in this category
            $matchedAddons = ProductAddon::whereIn('id', $addonIds)
                ->where('addon_category_id', $category->id)
                ->get();



            foreach ($matchedAddons as $addon) {
                // Find the requested quantity for this addon
                $requestedAddon = collect($item[$addonType])->firstWhere('id', $addon->id);
                $addonQuantity = $requestedAddon['quantity'] ?? 1; // Default to 1 if not specified

                $addons[] = [
                    'addon_type' => $addonType,
                    'addon_id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $addon->price,
                    'quantity' => $addonQuantity, // Add quantity
                ];

                // Calculate addon total with quantity
                $addonTotal += ($addon->price * $addonQuantity);
            }
        }

        return [
            'addons' => $addons,
            'addon_total' => $addonTotal
        ];
    }

    /**
     * Get card brand based on card number
     */
    protected function getCardBrand($cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } elseif (preg_match('/^6/', $cardNumber)) {
            return 'Discover';
        } elseif (preg_match('/^506/', $cardNumber)) {
            return 'Verve';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Display the specified order.
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with(['items.product.images', 'items.addons'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order
            ]
        ]);
    }

    /**
     * Cancel an order.
     */
    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found'
            ], 404);
        }

        if (!$order->canBeCancelled()) {
            $message = match ($order->status) {
                'cancelled' => 'Order is already cancelled',
                'dispatched' => 'Order has been dispatched and cannot be cancelled',
                'completed' => 'Order has been completed and cannot be cancelled',
                'rejected' => 'Order has been rejected and cannot be cancelled',
                default => 'Order cannot be cancelled'
            };

            return response()->json([
                'error' => true,
                'message' => $message
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update order status
            $order->update(['status' => 'cancelled']);

            // Restore product quantities
            foreach ($order->items as $item) {
                if ($item->product->track_quantity) {
                    $item->product->increment('quantity', $item->quantity);
                }
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Order cancelled successfully',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => 'Failed to cancel order'
            ], 500);
        }
    }

    /**
     * Validate a discount code.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'discount_code' => 'required|string|exists:discount_codes,code',
        ]);

        $user = Auth::user();
        $discountCode = DiscountCode::where('code', $request->discount_code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$discountCode) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid or expired discount code.'
            ], 400);
        }

        // Check usage limit
        if ($discountCode->usage_limit !== null && $discountCode->used_count >= $discountCode->usage_limit) {
            return response()->json([
                'error' => true,
                'message' => 'Discount code has reached its usage limit.'
            ], 400);
        }

        // Check minimum amount if provided in the request
        if ($discountCode->minimum_amount !== null && $request->has('total_amount') && $request->total_amount < $discountCode->minimum_amount) {
            return response()->json([
                'error' => true,
                'message' => 'Minimum order amount of ' . $discountCode->minimum_amount . ' is required for this discount code.'
            ], 400);
        }

        // Check if user has already used this coupon
        $alreadyUsed = DB::table('discount_code_user')
            ->where('discount_code_id', $discountCode->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyUsed) {
            return response()->json([
                'error' => true,
                'message' => 'You have already used this discount code.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Discount code validated successfully.',
            'data' => $discountCode
        ]);
    }

    /**
     * Get pending orders for the authenticated user (for debugging)
     */
    public function getPendingOrders(Request $request)
    {
        if (!config('app.debug')) {
            return response()->json(['error' => true, 'message' => 'Not available in production'], 404);
        }

        $user = $request->user();
        $pendingOrders = PendingOrder::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pendingOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'payment_reference' => $order->payment_reference,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'expires_at' => $order->expires_at,
                    'is_expired' => $order->isExpired(),
                    'created_at' => $order->created_at,
                ];
            })
        ]);
    }
}
