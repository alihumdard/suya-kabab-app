<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display user's cart.
     */
    public function index(Request $request)
    {
        $cartItems = Cart::with(['product.images'])
            ->forUser($request->user()->id)
            ->get();

        $subtotal = $cartItems->sum(function ($item) {
            return ($item->quantity * $item->product->price) + $item->addon_total;
        });

        $addonTotal = $cartItems->sum('addon_total');

        return response()->json([
            'error' => false,
            'data' => [
                'cart_items' => $cartItems,
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'count' => $cartItems->count()
            ]
        ]);
    }

    /**
     * Add item to cart.
     */
    public function store(AddToCartRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        // Check if product is active
        if ($product->status !== 'active' || !$product->isInStock()) {
            return response()->json([
                'error' => true,
                'message' => 'Product is not available or out of stock'
            ], 400);
        }

        // Check if item already exists in cart
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        // Calculate addon total
        $addonTotal = 0;
        $customizations = [];

        if ($request->has('customizations')) {
            foreach ($request->customizations as $customization) {
                $addon = \App\Models\ProductAddon::find($customization['id']);
                if ($addon) {
                    $customizations[] = [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => $addon->price,
                        'quantity' => $customization['quantity'],
                        'addon_category_id' => $addon->addon_category_id,
                        'category_name' => $addon->category->name
                    ];
                    $addonTotal += $addon->price * $customization['quantity'];
                }
            }
        }

        if ($cartItem) {
            // Update existing item
            $cartItem->update([
                'quantity' => $cartItem->quantity + $request->quantity,
                'customizations' => $customizations,
                'special_instructions' => $request->special_instructions,
                'addon_total' => $addonTotal
            ]);
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'customizations' => $customizations,
                'special_instructions' => $request->special_instructions,
                'addon_total' => $addonTotal
            ]);
        }

        $cartItem->load('product.images');

        return response()->json([
            'error' => false,
            'message' => 'Item added to cart',
            'data' => [
                'cart_item' => $cartItem
            ]
        ], 201);
    }

    /**
     * Display the specified cart item.
     */
    public function show($id, Request $request)
    {
        $cartItem = Cart::with(['product.images'])
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'error' => true,
                'message' => 'Cart item not found'
            ], 404);
        }

        return response()->json([
            'error' => false,
            'data' => $cartItem
        ], 200);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [

            'action' => ['required', 'string', 'in:increment,decrement'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()
            ], 422);
        }

        $cartItem = Cart::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'error' => true,
                'message' => 'Cart item not found'
            ], 404);
        }

        // Handle increment/decrement action
        if ($request->action === 'increment') {
            $newQuantity = $cartItem->quantity + 1;
        } else { // decrement
            $newQuantity = $cartItem->quantity - 1;

            // Prevent quantity from going below 1
            if ($newQuantity < 1) {
                return response()->json([
                    'error' => true,
                    'message' => 'Quantity cannot be less than 1. Use delete endpoint to remove item.'
                ], 400);
            }
        }

        $cartItem->update(['quantity' => $newQuantity]);
        $cartItem->load('product.images');

        return response()->json([
            'error' => false,
            'message' => 'Cart item updated',
            'data' => $cartItem
        ], 200);
    }

    /**
     * Remove item from cart.
     */
    public function destroy($id, Request $request)
    {
        $user = Auth::user();
        $cartItem = Cart::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'error' => true,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'error' => false,
            'message' => 'Item removed from cart'
        ], 200);
    }
}