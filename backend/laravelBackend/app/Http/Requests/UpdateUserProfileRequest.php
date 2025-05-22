<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Ensures the user is authenticated before processing the request
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullName' => [
                'sometimes', // Validate 'fullName' only if it is present in the request.
                'required',  // If 'fullName' is present, it must not be empty.
                'string',    // Must be a string.
                'min:2',     // Minimum 2 characters.
                'max:255',   // Maximum 255 characters.
                'regex:/^[\p{L}\s\'\-]+$/u', // Allows Unicode letters, spaces, apostrophes, hyphens.
            ],
            // Add other fields to update here if needed, e.g., 'email'
            // 'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fullName.required' => 'The full name cannot be empty if you intend to change it.',
            'fullName.string'   => 'The full name must be a text value. Please provide a valid name.',
            'fullName.min'      => 'The full name must be at least :min characters long.',
            'fullName.max'      => 'The full name cannot be longer than :max characters.',
            'fullName.regex'    => 'The full name contains invalid characters. Only letters, spaces, hyphens (-), and apostrophes (\') are allowed.',
            // 'email.required' => 'The email address cannot be empty if you intend to change it.',
            // 'email.email'    => 'Please provide a valid email address.',
            // 'email.unique'   => 'This email address is already taken.',
        ];
    }
}
