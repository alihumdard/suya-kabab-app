<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.customizations' => 'nullable|array',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.addon_total' => 'nullable|numeric|min:0',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|string',
            'delivery_phone' => 'required|string|max:20',
            'delivery_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'use_rewards_balance' => 'boolean',
            'rewards_amount' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string',
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Order items are required.',
            'items.min' => 'At least one item must be ordered.',
            'items.*.product_id.required' => 'Product ID is required for each item.',
            'items.*.product_id.exists' => 'Product does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'delivery_method.required' => 'Delivery method is required.',
            'delivery_method.in' => 'Delivery method must be either pickup or delivery.',
            'delivery_address.required_if' => 'Delivery address is required when delivery method is delivery.',
            'delivery_phone.required' => 'Delivery phone is required.',
        ];
    }
}