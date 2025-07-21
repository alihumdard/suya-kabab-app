<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\HomeResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PromotionResource;

class HomeController extends Controller
{
    /**
     * Get home page data including user, products, categories, and active promotions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get user if authenticated
            $user = $request->user();

            // Get all active products with their relationships
            $productsQuery = Product::with(['category', 'images', 'addons'])
                ->active();

            // Eager load favorites relationship for authenticated users
            if ($user) {
                $productsQuery->with([
                    'favoritedBy' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }
                ]);
            }

            $products = $productsQuery->get();

            // Get all active categories with product count
            $categories = Category::with(['images'])
                ->active()
                ->orderBy('name')
                ->withCount('activeProducts')
                ->get();

            // Get all active promotions with images
            $promotions = Promotion::with('images')
                ->active()
                ->ordered()
                ->get();

            // Prepare response data
            $responseData = [
                'user' => $user,
                'products' => $products,
                'categories' => $categories,
                'promotions' => $promotions,
            ];

            return response()->json([
                'error' => false,
                'message' => 'Home data retrieved successfully',
                'data' => new HomeResource($responseData),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error retrieving home data: ' . $e->getMessage()
            ], 500);
        }
    }
}
