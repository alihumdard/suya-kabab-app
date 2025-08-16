<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Review;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns this review
        $review = Review::where('id', $this->route('review'))
            ->where('user_id', auth()->id())
            ->first();

        if (!$review) {
            return false;
        }

        // Check if review is within editable timeframe (24 hours)
        $hoursSinceCreation = $review->created_at->diffInHours(now());
        if ($hoursSinceCreation > 24) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
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
            'rating' => 'rating',
            'comment' => 'comment',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        $review = Review::find($this->route('review'));

        if (!$review) {
            abort(404, 'Review not found.');
        }

        if ($review->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to update this review.');
        }

        // Check if review is within editable timeframe
        $hoursSinceCreation = $review->created_at->diffInHours(now());
        if ($hoursSinceCreation > 24) {
            abort(400, 'Reviews can only be edited within 24 hours of creation.');
        }
    }
}
