<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;

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

        DB::beginTransaction();

        try {
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

                $itemTotal = $product->price * $item['quantity'];
                $itemAddonTotal = $item['addon_total'] ?? 0;
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
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->track_quantity) {
                    $product->decrement('quantity', $item['quantity']);
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


}