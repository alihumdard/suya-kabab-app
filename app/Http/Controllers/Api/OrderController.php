<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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
            'success' => true,
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
     * Create a new order.
     */
    public function store(CreateOrderRequest $request)
    {
        $user = $request->user();

        // Get cart items
        $cartItems = Cart::with(['product'])
            ->forUser($user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'Cart is empty'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $addonTotal = 0;
            $orderItems = [];

            // Validate cart items and calculate total
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                if ($product->status !== 'active') {
                    throw new \Exception("Product {$product->name} is not available");
                }

                if (!$product->isInStock()) {
                    throw new \Exception("Product {$product->name} is out of stock");
                }

                if ($product->track_quantity && $product->quantity < $cartItem->quantity) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $itemTotal = $product->price * $cartItem->quantity;
                $subtotal += $itemTotal;
                $addonTotal += $cartItem->addon_total;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                    'total' => $itemTotal,
                    'customizations' => $cartItem->customizations,
                    'special_instructions' => $cartItem->special_instructions,
                    'addon_total' => $cartItem->addon_total,
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

            // Calculate rewards discount
            $rewardsDiscount = 0;
            if ($request->use_rewards_balance && $request->rewards_amount) {
                $user = $request->user();
                $availableRewards = $user->rewards_balance ?? 0;
                $rewardsDiscount = min($request->rewards_amount, $availableRewards, $subtotal);
            }

            $totalAmount = $subtotal + $deliveryCharges - $discountAmount - $rewardsDiscount;

            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'tax_amount' => 0, // Can be calculated based on business logic
                'shipping_amount' => $deliveryCharges,
                'discount_amount' => $discountAmount,
                'total_amount' => max(0, $totalAmount),
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_address,
                'delivery_phone' => $request->delivery_phone,
                'delivery_instructions' => $request->delivery_instructions,
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Update product quantities
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                if ($product->track_quantity) {
                    $product->decrement('quantity', $cartItem->quantity);
                }
            }

            // Use rewards balance if applicable
            if ($rewardsDiscount > 0) {
                $user->useRewardsBalance($rewardsDiscount);
            }

            // Mark discount code as used
            if ($discountCode && $discountAmount > 0) {
                $discountCode->markAsUsed();
            }

            // Clear all cart items after successful order
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            $order->load(['items.product.images']);

            return response()->json([
                'error' => false,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified order.
     */
    public function show($id, Request $request)
    {
        $order = Order::with(['items.product.images'])
            ->where('user_id', $request->user()->id)
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
    public function cancel($id, Request $request)
    {
        $order = Order::where('user_id', $request->user()->id)
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
                'processing' => 'Order is being processed and cannot be cancelled',
                'shipped' => 'Order has been shipped and cannot be cancelled',
                'delivered' => 'Order has been delivered and cannot be cancelled',
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
     * Get order review details for checkout.
     */
    public function review(Request $request)
    {
        $user = $request->user();

        // Get cart items
        $cartItems = Cart::with(['product.images'])
            ->forUser($user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return ($item->quantity * $item->product->price) + $item->addon_total;
        });

        $addonTotal = $cartItems->sum('addon_total');

        // Delivery method and charges
        $deliveryMethod = $request->get('delivery_method', 'pickup');
        $deliveryCharges = Setting::calculateDeliveryCharges($subtotal, $deliveryMethod);

        // Calculate discounts using helper function
        $discountResult = calculateDiscount(
            $request->discount_code,
            $subtotal,
            $user,
            $request->boolean('use_rewards_balance'),
            $request->rewards_amount
        );

        // Return error if discount code is invalid
        if (!empty($request->discount_code) && !empty($discountResult['error_message'])) {
            return response()->json([
                'error' => true,
                'message' => $discountResult['error_message']
            ], 400);
        }

        $totalAmount = $subtotal + $deliveryCharges - $discountResult['discount_amount'] - $discountResult['rewards_discount'];

        return response()->json([
            'error' => false,
            'data' => [
                'cart_items' => $cartItems,
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'delivery_charges' => $deliveryCharges,
                'delivery_method' => $deliveryMethod,
                'discount_amount' => $discountResult['discount_amount'],
                'discount_code' => $discountResult['discount_code'],
                'discount_details' => $discountResult['discount_details'],
                'rewards_discount' => $discountResult['rewards_discount'],
                'total_amount' => max(0, $totalAmount),
                'original_total' => $subtotal + $deliveryCharges,
                'total_savings' => $discountResult['total_savings'],
                'user' => [
                    'name' => $user->name,
                    'address' => $user->address,
                    'phone' => $user->phone,
                    'rewards_balance' => $user->rewards_balance ?? 0
                ]
            ]
        ]);
    }



    /**
     * Calculate order total with all discounts and charges.
     */
    public function calculateTotal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_method' => 'required|in:pickup,delivery',
            'use_rewards_balance' => 'boolean',
            'rewards_amount' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Get cart items
        $cartItems = Cart::with(['product.images'])
            ->forUser($user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Calculate subtotal
        $subtotal = $cartItems->sum(function ($item) {
            return ($item->quantity * $item->product->price) + $item->addon_total;
        });

        $addonTotal = $cartItems->sum('addon_total');

        // Calculate delivery charges dynamically
        $deliveryCharges = Setting::calculateDeliveryCharges($subtotal, $request->delivery_method);

        // Calculate discounts using helper function
        $discountResult = calculateDiscount(
            $request->discount_code,
            $subtotal,
            $user,
            $request->boolean('use_rewards_balance'),
            $request->rewards_amount
        );

        // Return error if discount code is invalid
        if (!empty($request->discount_code) && !empty($discountResult['error_message'])) {
            return response()->json([
                'success' => false,
                'message' => $discountResult['error_message']
            ], 422);
        }

        $totalAmount = $subtotal + $deliveryCharges - $discountResult['discount_amount'] - $discountResult['rewards_discount'];

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'delivery_charges' => $deliveryCharges,
                'discount_amount' => $discountResult['discount_amount'],
                'discount_details' => $discountResult['discount_details'],
                'rewards_discount' => $discountResult['rewards_discount'],
                'total_amount' => max(0, $totalAmount),
                'total_savings' => $discountResult['total_savings'],
                'available_rewards' => $user->rewards_balance ?? 0
            ]
        ]);
    }
}