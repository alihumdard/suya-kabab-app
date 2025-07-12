<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use App\Models\DiscountCode;
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|string',
            'delivery_phone' => 'required|string|max:20',
            'delivery_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'use_rewards_balance' => 'boolean',
            'rewards_amount' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $orderItems = [];

            // Validate items and calculate total
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

                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                ];
            }

            // Calculate delivery charges
            $deliveryCharges = $request->delivery_method === 'delivery' ? 100 : 0;
            
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
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->track_quantity) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            // Use rewards balance if applicable
            if ($rewardsDiscount > 0) {
                $request->user()->useRewardsBalance($rewardsDiscount);
            }

            // Mark discount code as used
            if ($discountCode && $discountAmount > 0) {
                $discountCode->markAsUsed();
            }

            // Clear cart items for the products that were ordered
            Cart::where('user_id', $request->user()->id)
                ->whereIn('product_id', array_column($request->items, 'product_id'))
                ->delete();

            DB::commit();

            $order->load(['items.product.images']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
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
                'success' => false,
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
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
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
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'order' => $order
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
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

        // Default delivery method and charges
        $deliveryMethod = $request->get('delivery_method', 'pickup');
        $deliveryCharges = $deliveryMethod === 'delivery' ? 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'cart_items' => $cartItems,
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'delivery_charges' => $deliveryCharges,
                'delivery_method' => $deliveryMethod,
                'user' => [
                    'name' => $user->name,
                    'address' => $user->address,
                    'phone' => $user->phone,
                    'rewards_balance' => $user->rewards_balance ?? 0
                ],
                'total_amount' => $subtotal + $deliveryCharges
            ]
        ]);
    }

    /**
     * Validate and apply discount code.
     */
    public function validateDiscountCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $discountCode = DiscountCode::where('code', $request->code)->first();

        if (!$discountCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid discount code'
            ], 400);
        }

        if (!$discountCode->isValid($request->subtotal)) {
            return response()->json([
                'success' => false,
                'message' => 'Discount code is not valid or expired'
            ], 400);
        }

        $discountAmount = $discountCode->calculateDiscount($request->subtotal);

        return response()->json([
            'success' => true,
            'data' => [
                'discount_code' => $discountCode->code,
                'discount_amount' => $discountAmount,
                'new_total' => $request->subtotal - $discountAmount
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

        // Calculate delivery charges
        $deliveryCharges = $request->delivery_method === 'delivery' ? 100 : 0;

        // Calculate discount
        $discountAmount = 0;
        if ($request->discount_code) {
            $discountCode = DiscountCode::where('code', $request->discount_code)->first();
            if ($discountCode && $discountCode->isValid($subtotal)) {
                $discountAmount = $discountCode->calculateDiscount($subtotal);
            }
        }

        // Calculate rewards discount
        $rewardsDiscount = 0;
        if ($request->use_rewards_balance && $request->rewards_amount) {
            $availableRewards = $user->rewards_balance ?? 0;
            $rewardsDiscount = min($request->rewards_amount, $availableRewards, $subtotal);
        }

        $totalAmount = $subtotal + $deliveryCharges - $discountAmount - $rewardsDiscount;

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'delivery_charges' => $deliveryCharges,
                'discount_amount' => $discountAmount,
                'rewards_discount' => $rewardsDiscount,
                'total_amount' => max(0, $totalAmount),
                'available_rewards' => $user->rewards_balance ?? 0
            ]
        ]);
    }
}