<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserProfilePictureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
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
            'profile_picture' => [
                'required',
                'image', // Checks if the file is an image (jpeg, png, bmp, gif, svg, or webp)
                'mimes:jpeg,png,jpg,gif,webp', // Specify allowed image MIME types
                'max:5120', // Max file size in kilobytes (5MB = 5 * 1024 KB)
            ],
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
            'profile_picture.required' => 'Please select a profile picture to upload.',
            'profile_picture.image'    => 'The uploaded file must be an image.',
            'profile_picture.mimes'    => 'Only JPEG, PNG, JPG, GIF, and WEBP images are allowed.',
            'profile_picture.max'      => 'The profile picture may not be greater than 5MB.',
        ];
    }
}
