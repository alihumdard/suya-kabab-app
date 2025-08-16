<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'rating',
        'comment',
    ];

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the review.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the products from the order that can be reviewed.
     */
    public function products()
    {
        return $this->order->items->map(function ($item) {
            return $item->product;
        });
    }

    /**
     * Scope a query to filter by rating.
     */
    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to only include reviews for completed orders.
     */
    public function scopeForCompletedOrders($query)
    {
        return $query->whereHas('order', function ($query) {
            $query->where('status', 'completed');
        });
    }

    /**
     * Check if this review is for a completed order.
     */
    public function isForCompletedOrder()
    {
        return $this->order && $this->order->status === 'completed';
    }

    /**
     * Get the average rating for a specific order.
     */
    public static function getAverageRatingForOrder($orderId)
    {
        return static::where('order_id', $orderId)->avg('rating');
    }

    /**
     * Get the total number of reviews for a specific order.
     */
    public static function getReviewCountForOrder($orderId)
    {
        return static::where('order_id', $orderId)->count();
    }
}