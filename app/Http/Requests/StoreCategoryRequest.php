<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow authorized admin users to create categories
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'status.required' => 'Category status is required.',
            'status.in' => 'Category status must be either active or inactive.',
            'sort_order.integer' => 'Sort order must be a valid number.',
            'sort_order.min' => 'Sort order cannot be negative.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif.',
            'image.max' => 'Image size cannot exceed 2MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sort_order' => 'sort order',
        ];
    }
}
