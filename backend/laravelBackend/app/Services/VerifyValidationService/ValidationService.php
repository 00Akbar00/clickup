<?php

namespace App\Services\VerifyValidationService;

use App\Rules\ContainsLetters;
use App\Rules\ValidDescriptionCharacters;
use App\Rules\ValidNameCharacters;
use Illuminate\Validation\Rule;

class ValidationService
{
    public static function nameRules(): array
    {
        $rules = [
            'required',
            'string',
            'max:25',
            new ContainsLetters,
            new ValidNameCharacters,
        ];

        $messages = [
            'full_name.required' => 'The full name is required.',
            'full_name.max' => 'The full name must not exceed 25 characters.',

        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public static function descriptionRules(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            new ValidDescriptionCharacters,
        ];
    }
    public static function projectRules(bool $includeAdditionalFields = true): array
    {
        $rules = [];
        $rules['visibility'] = ['nullable', 'string', Rule::in(['public', 'private'])];
        $rules['color_code'] = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        $rules['status'] = ['nullable', 'string', Rule::in(['active', 'archived'])];

        return $rules;
    }
    public static function emailSignupRules(): array
    {
        $rules = [
            'required',
            'email:rfc,dns',
            'unique:users,email',
            function ($attribute, $value, $fail) {
                if (preg_match('/\s/', $value)) {
                    $fail("The $attribute must not contain any spaces.");
                }
            },
        ];

        $messages = [
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public static function emailLoginRules(): array
    {
        $rules = [
            'email' => [
                'required',
                'email:rfc,dns',
                'exists:users,email',
                function ($attribute, $value, $fail) {
                    if (preg_match('/\s/', $value)) {
                        $fail("The $attribute must not contain any spaces.");
                    }
                },
            ],
        ];

        $messages = [
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'We could not find a user with that email address.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }



    public static function passwordRules(bool $requireConfirmation = false): array
    {
        $rules = [
            'password' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/'
            ]
        ];

        if ($requireConfirmation) {
            $rules['password'][] = 'confirmed';
        }

        $messages = [
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.max' => 'The password must not be more than 20 characters.',
            'password.regex' => 'The password must include at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public static function avatarRules(): array
    {
        $rules = [
            'profile_picture_url' => [
                'nullable',
                'file',
                'max:2048',
                'mimetypes:image/jpeg,image/png',
            ],
        ];

        $messages = [
            'profile_picture_url.file' => 'The avatar must be a valid file.',
            'profile_picture_url.mimetypes' => 'The avatar must be a JPEG, JPG, or PNG image.',
            'profile_picture_url.max' => 'The avatar size must not exceed 2 MB.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }


    public static function workspaceRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:25',
                new ContainsLetters,
                new ValidNameCharacters,
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
                new ValidDescriptionCharacters,
            ],
            'logo' => [
                'nullable',
                'file',
                'mimes:jpeg,jpg,png',
                'max:2048'
            ]
        ];
    }
}
