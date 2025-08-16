<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;
use App\Models\Review;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                function ($attribute, $value, $fail) {
                    // Check if user owns this order
                    $order = Order::where('id', $value)
                        ->where('user_id', auth()->id())
                        ->first();

                    if (!$order) {
                        $fail('Order not found or you do not have permission to review this order.');
                        return;
                    }

                    // Check if order is completed
                    if ($order->status !== 'completed') {
                        $fail('You can only review orders that have been completed. Current order status: ' . ucfirst($order->status));
                        return;
                    }

                    // Check if user already reviewed this order
                    $existingReview = Review::where('user_id', auth()->id())
                        ->where('order_id', $value)
                        ->first();

                    if ($existingReview) {
                        $fail('You have already reviewed this order.');
                        return;
                    }
                }
            ],
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required.',
            'order_id.integer' => 'Order ID must be a valid number.',
            'order_id.exists' => 'The specified order does not exist.',
            'rating.required' => 'Rating is required.',
            'rating.integer' => 'Rating must be a number.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'comment.max' => 'Comment cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
            'order_id' => 'order',
            'rating' => 'rating',
            'comment' => 'comment',
        ];
    }
}
