<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'success' => true,
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);

        // Check if product is active
        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available'
            ], 400);
        }

        // Check stock
        if (!$product->isInStock()) {
            return response()->json([
                'success' => false,
                'message' => 'Product is out of stock'
            ], 400);
        }

        // Check if item already exists in cart
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Update existing item
            $cartItem->update([
                'quantity' => $cartItem->quantity + $request->quantity
            ]);
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        $cartItem->load('product.images');

        return response()->json([
            'success' => true,
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
            ->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart_item' => $cartItem
            ]
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $cartItem = Cart::where('user_id', $request->user()->id)
            ->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->load('product.images');

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated',
            'data' => [
                'cart_item' => $cartItem
            ]
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function destroy($id, Request $request)
    {
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }
}