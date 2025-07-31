<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\AddonCategory;
use App\Models\Order;
use Illuminate\Support\Str;

use App\Models\DiscountCode;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display user's orders.
     */
    public function index(Request $request)
    {   
        $user = auth()->user();
        $orders = Order::with(['items.product.images'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'error' => false,
            'message' => 'Orders retrieved successfully',
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
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $orderItems = [];

            // Validate order items
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

                // Get addon total from request or process addons if not provided
                $itemAddonTotal = $item['addon_total'] ?? 0;
                $itemAddons = [];
                
                // If addon_total is not provided, process addons to get the total
                if (!isset($item['addon_total'])) {
                    $addonResult = $this->processAddons($item);
                    $itemAddons = $addonResult['addons'];
                    $itemAddonTotal = $addonResult['addon_total'];
                }

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $item['total'] ?? ($product->price * $item['quantity']),
                    'customizations' => $item['customizations'] ?? null,
                    'special_instructions' => $item['special_instructions'] ?? null,
                    'addon_total' => $itemAddonTotal,
                    'addons' => !empty($itemAddons) ? $itemAddons : null,
                ];
            }

            // Get subtotal and total from request
            $subtotal = $request->subtotal;
            $totalAmount = $request->total_amount;
            $deliveryCharges = $request->delivery_charges ?? 0;
            $discountAmount = $request->discount_amount ?? 0;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'shipping_amount' => $deliveryCharges,
                'discount_amount' => $discountAmount,
                'total_amount' => max(0, $totalAmount),
                'delivery_method' => $request->delivery_method,
                'payment_method' => $request->payment_method,
                'delivery_address' => $request->delivery_address,
                'delivery_phone' => $request->delivery_phone,
                'delivery_instructions' => $request->delivery_instructions,
                'status' => 'pending', // Always set status to pending by default
            ]);

            // Create order items
            foreach ($orderItems as $index => $item) {
                $orderItem = $order->items()->create($item);
                
                // Save only the addons that were in the original request for this item
                $requestItem = $request->items[$index] ?? [];
                $baseFields = ['product_id', 'quantity', 'customizations', 'special_instructions', 'addon_total'];
                $requestedAddonTypes = array_diff(array_keys($requestItem), $baseFields);
                
                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        // Only save addons that match the requested types in the original request
                        if (in_array(strtolower($addon['addon_type']), array_map('strtolower', $requestedAddonTypes))) {
                            $orderItem->addons()->create([
                                'product_addon_id' => $addon['addon_id'],
                                'quantity' => $item['quantity'],
                                'price' => $addon['price']
                            ]);
                        }
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

            // Eager load all necessary relationships with addon categories
            $order->load([
                'items' => function($query) {
                    $query->with([
                        'product.images',
                        'addons' => function($query) {
                            $query->with(['productAddon.category']);
                        }
                    ]);
                }
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order)
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

        \Log::info('Processing addons for item', [
            'product_id' => $item['product_id'],
            'addon_fields' => $addonFields,
            'item_data' => $item
        ]);

        foreach ($addonFields as $addonType) {
            // Skip if the addon type is not an array
            if (!is_array($item[$addonType] ?? null)) {
                \Log::debug('Skipping non-array addon type', ['addon_type' => $addonType]);
                continue;
            }
            
            // Log the addon type being processed
            \Log::debug('Processing addon type', ['addon_type' => $addonType]);
            
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
                \Log::warning('Category not found', ['addon_type' => $addonType]);
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
                $addons[] = [
                    'addon_type' => $addonType,
                    'addon_id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $addon->price,
                ];
                $addonTotal += $addon->price * $item['quantity'];
            }
        }

        return [
            'addons' => $addons,
            'addon_total' => $addonTotal
        ];
    }

    /**
     * Display the specified order.
     */
    public function show($id)
    {
       $user = auth()->user();
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
        $user = auth()->user();
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
                'processing' => 'Order is being processed and cannot be cancelled',
                'dispatched' => 'Order has been dispatched and cannot be cancelled',
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
     * Validate a discount code.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'discount_code' => 'required|string|exists:discount_codes,code',
        ]);

        $user = auth()->user();
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
}
