<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,other'],
            'profile_image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'address' => ['sometimes', 'nullable', 'string'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'phone.string' => 'Phone must be a string.',
            'phone.max' => 'Phone cannot exceed 20 characters.',
            'gender.in' => 'Gender must be one of: male, female, other.',
            'profile_image.image' => 'Profile image must be an image file.',
            'profile_image.mimes' => 'Profile image must be a file of type: jpeg, png, jpg, gif.',
            'profile_image.max' => 'Profile image file size cannot exceed 2MB.',
            'profile_image.dimensions' => 'Profile image dimensions must be between 100x100 and 2000x2000 pixels.',
            'date_of_birth.date' => 'Date of birth must be a valid date.',
            'address.string' => 'Address must be a string.',
            'city.string' => 'City must be a string.',
            'city.max' => 'City cannot exceed 100 characters.',
            'state.string' => 'State must be a string.',
            'state.max' => 'State cannot exceed 100 characters.',
            'postal_code.string' => 'Postal code must be a string.',
            'postal_code.max' => 'Postal code cannot exceed 20 characters.',
            'country.string' => 'Country must be a string.',
            'country.max' => 'Country cannot exceed 100 characters.',
        ];
    }
}