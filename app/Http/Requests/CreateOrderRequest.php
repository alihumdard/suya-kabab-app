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
            'items.*.special_instructions' => 'nullable|string',
            'items.*.addon_total' => 'nullable|numeric|min:0',
            'items.*' => [
                function ($attribute, $value, $fail) {
                    // Get all addon fields (any field that's not in the base fields)
                    $baseFields = ['product_id', 'quantity', 'customizations', 'special_instructions', 'addon_total'];
                    $addonFields = array_diff(array_keys($value), $baseFields);

                    foreach ($addonFields as $addonType) {
                        // Each addon type should be an array of objects with 'id'
                        if (!is_array($value[$addonType])) {
                            $fail("The $addonType must be an array of add-ons.");
                            continue;
                        }

                        foreach ($value[$addonType] as $index => $addon) {
                            if (!isset($addon['id'])) {
                                $fail("Each item in $addonType must have an 'id' field.");
                                continue;
                            }

                            // Verify the addon exists and is active
                            $addonExists = \App\Models\ProductAddon::where('id', $addon['id'])
                                ->where('status', 'active')
                                ->exists();

                            if (!$addonExists) {
                                $fail("The selected add-on in $addonType at index $index does not exist or is not available.");
                            }
                        }
                    }
                },
            ],
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|string',
            'delivery_phone' => 'required|string|max:20',
            'delivery_instructions' => 'nullable|string',
            'discount_code' => 'nullable|string',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string',
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
