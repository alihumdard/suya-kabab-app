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
        $rules = [
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
                        // Each addon type should be an array of objects with 'id' and 'quantity'
                        if (!is_array($value[$addonType])) {
                            $fail("The $addonType must be an array of add-ons.");
                            continue;
                        }

                        foreach ($value[$addonType] as $index => $addon) {
                            if (!isset($addon['id'])) {
                                $fail("Each item in $addonType must have an 'id' field.");
                                continue;
                            }

                            if (!isset($addon['quantity'])) {
                                $fail("Each item in $addonType must have a 'quantity' field.");
                                continue;
                            }

                            if (!is_numeric($addon['quantity']) || $addon['quantity'] < 1) {
                                $fail("Quantity for item in $addonType at index $index must be a positive number.");
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
            'payment_method' => 'nullable|string|in:card,cod,flutterwave',
        ];

        // Add card payment validation rules if payment method is card
        if ($this->input('payment_method') === 'card') {
            $rules['payment_method'] = 'required|string|in:card,cod,flutterwave';
            $rules['card_details'] = 'required|array';
            $rules['card_details.card_number'] = 'required|string|min:13|max:19';
            $rules['card_details.cvv'] = 'required|string|min:3|max:4';
            $rules['card_details.expiry_month'] = 'required|string|size:2|in:01,02,03,04,05,06,07,08,09,10,11,12';
            $rules['card_details.expiry_year'] = ['required', 'string', 'size:2', function ($attribute, $value, $fail) {
                if (!ctype_digit($value)) {
                    $fail('The expiry year must contain only digits.');
                }
            }];
            $rules['card_details.card_holder_name'] = 'required|string|max:255';
        } else {
            // For other payment methods, payment_method is optional and defaults to COD
            $rules['payment_method'] = 'nullable|string|in:card,cod,flutterwave';
        }

        return $rules;
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
            'card_details.required' => 'Card details are required for card payments.',
            'card_details.card_number.required' => 'Card number is required.',
            'card_details.card_number.min' => 'Card number must be at least 13 digits.',
            'card_details.card_number.max' => 'Card number must not exceed 19 digits.',
            'card_details.cvv.required' => 'CVV is required.',
            'card_details.cvv.min' => 'CVV must be at least 3 digits.',
            'card_details.cvv.max' => 'CVV must not exceed 4 digits.',
            'card_details.expiry_month.required' => 'Card expiry month is required.',
            'card_details.expiry_month.in' => 'Card expiry month must be between 01 and 12.',
            'card_details.expiry_month.size' => 'Card expiry month must be 2 digits (e.g., 09).',
            'card_details.expiry_year.required' => 'Card expiry year is required.',
            'card_details.expiry_year.size' => 'Card expiry year must be 2 digits (e.g., 25).',
            'card_details.card_holder_name.required' => 'Card holder name is required.',
            'card_details.card_holder_name.max' => 'Card holder name must not exceed 255 characters.',
        ];
    }
}
