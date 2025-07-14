<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'customizations' => 'nullable|array',
            'customizations.*.id' => 'required|exists:product_addons,id',
            'customizations.*.quantity' => 'required|integer|min:1',
            'special_instructions' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'customizations.*.id.required' => 'Addon ID is required.',
            'customizations.*.id.exists' => 'Selected addon does not exist.',
            'customizations.*.quantity.required' => 'Addon quantity is required.',
            'customizations.*.quantity.integer' => 'Addon quantity must be a number.',
            'customizations.*.quantity.min' => 'Addon quantity must be at least 1.',
            'special_instructions.max' => 'Special instructions cannot exceed 500 characters.',
        ];
    }
}