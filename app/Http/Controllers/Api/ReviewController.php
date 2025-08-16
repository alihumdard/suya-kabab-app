<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Order;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Display a listing of user's reviews.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $reviews = Review::with(['order.items.product.images'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => ReviewResource::collection($reviews),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request)
    {
        $user = Auth::user();
        $orderId = $request->order_id;

        DB::beginTransaction();

        try {
            $review = Review::create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            DB::commit();

            // Load relationships for response
            $review->load(['order.items.product.images']);

            return response()->json([
                'error' => false,
                'message' => 'Review submitted successfully',
                'data' => [
                    'review' => new ReviewResource($review)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => 'Failed to submit review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show($id, Request $request)
    {
        $review = Review::with(['order.items.product.images'])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$review) {
            return response()->json([
                'error' => true,
                'message' => 'Review not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'review' => new ReviewResource($review)
            ]
        ]);
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, $id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        $review->update($request->only(['rating', 'comment']));

        return response()->json([
            'error' => false,
            'message' => 'Review updated successfully',
            'data' => [
                'review' => new ReviewResource($review)
            ]
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy($id, Request $request)
    {
        $review = Review::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$review) {
            return response()->json([
                'error' => true,
                'message' => 'Review not found'
            ], 404);
        }

        // Check if review is within deletable timeframe (24 hours)
        $hoursSinceCreation = $review->created_at->diffInHours(now());
        if ($hoursSinceCreation > 24) {
            return response()->json([
                'error' => true,
                'message' => 'Reviews can only be deleted within 24 hours of creation'
            ], 400);
        }

        $review->delete();

        return response()->json([
            'error' => false,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Get all reviews for a specific order.
     */
    public function getOrderReviews($orderId, Request $request)
    {
        $user = Auth::user();

        // Check if the order exists and belongs to the user
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found or you do not have permission to view this order'
            ], 404);
        }

        // Get all reviews for this order
        $reviews = Review::with(['user'])
            ->where('order_id', $orderId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                ],
                'reviews' => ReviewResource::collection($reviews),
                'total_reviews' => $reviews->count(),
                'average_rating' => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : 0,
            ]
        ]);
    }

    /**
     * Get orders that can be reviewed by the user.
     */
    public function getReviewableOrders(Request $request)
    {
        $user = $request->user();

        $reviewableOrders = Order::with(['items.product.images'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('reviews', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $reviewableOrders->items(),
                'pagination' => [
                    'current_page' => $reviewableOrders->currentPage(),
                    'last_page' => $reviewableOrders->lastPage(),
                    'per_page' => $reviewableOrders->perPage(),
                    'total' => $reviewableOrders->total(),
                ]
            ]
        ]);
    }
}
