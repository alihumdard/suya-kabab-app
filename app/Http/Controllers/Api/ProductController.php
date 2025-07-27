<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'addons'])
            ->active();

        // Eager load favorites relationship for authenticated users
        if (Auth::check()) {
            $query->with('favoritedBy');
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        // Filter by user's favorites (requires authentication)
        if ($request->has('favorites') && $request->boolean('favorites')) {
            $user = $request->user();
            if ($user) {
                $favoriteProductIds = $user->favorites()->pluck('product_id');
                $query->whereIn('id', $favoriteProductIds);
            }
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sort
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);
        $pagination = [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'from' => $products->firstItem(),
            'to' => $products->lastItem(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'products' => ProductResource::collection($products->items()),
                'pagination' => $pagination
            ]
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id)
    {
        $query = Product::with(['category', 'images', 'addons']);

        // Eager load favorites relationship for authenticated users
        if (Auth::check()) {
            $query->with('favoritedBy');
        }

        $product = $query->active()->find($id);

        // ...existing code...

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or inactive'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Get product customization options (addons).
     */
    public function customizations(string $id)
    {
        $product = Product::with([
            'addons' => function ($query) {
                $query->where('status', 'active')->orderBy('sort_order');
            }
        ])->active()->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or inactive'
            ], 404);
        }

        if ($product->addons->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'addons' => [],
                    'has_customization' => false,
                    'message' => 'No customization options available for this product'
                ]
            ]);
        }

        // Format addons for customization
        $addons = $product->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->name,
                'slug' => $addon->slug,
                'description' => $addon->description,
                'price' => $addon->price,
                'image' => $addon->image,
                'sku' => $addon->sku,
                'is_required' => $addon->pivot->is_required,
                'min_quantity' => $addon->pivot->min_quantity,
                'max_quantity' => $addon->pivot->max_quantity,
                'sort_order' => $addon->pivot->sort_order,
                'in_stock' => $addon->isInStock(),
                'track_quantity' => $addon->track_quantity,
                'available_quantity' => $addon->track_quantity ? $addon->quantity : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'addons' => $addons,
                'has_customization' => $addons->isNotEmpty()
            ]
        ]);
    }

    /**
     * Get authenticated user's favorite products.
     */
    public function favorites(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $products = $user->favorites()
            ->with([
                'category',
                'images',
                'favoritedBy'
            ])
            ->where('status', 'active')
            ->latest('user_favorites.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)
        ]);
    }



    /**
     * Toggle product favorite status for user.
     */
    public function toggleFavorite(Request $request, $id)
    {
        $user = Auth::user();
        $product = Product::findOrFail($id);

        $isFavorite = $user->favorites()->where('product_id', $id)->exists();

        if ($isFavorite) {
            $user->favorites()->detach($id);
            $message = 'Product removed from favorites successfully';
        } else {
            $user->favorites()->attach($id);
            $message = 'Product added to favorites successfully';
        }

        // Refresh the product with relationships to get updated is_favorite status
        $product = $product->fresh([
            'images',
            'favoritedBy'
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new ProductResource($product)
        ]);
    }
}
