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
        return [
            'required',
            'string',
            'max:25',
            new ContainsLetters,
            new ValidNameCharacters,
        ];
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
    public static function visibilityRules(): array
    {
        return ['nullable', 'in:public,private'];
    }

    public static function colorCodeRules(): array
    {
        return ['nullable', 'string', 'max:7'];
    }

    public static function projectRules(bool $includeAdditionalFields = true): array
    {
        $rules = [];
        $rules['visibility'] = ['nullable', 'string', Rule::in(['public', 'private', 'team'])];
        $rules['color_code'] = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        
        return $rules;
    }
    public static function emailSignupRules(): array
    {
        return [
            'required',
            'email:rfc,dns',
            'unique:users,email',
            function ($attribute, $value, $fail) {
                if (preg_match('/\s/', $value)) {
                    $fail("The $attribute must not contain any spaces.");
                }
            },
        ];
    }

    public static function emailLoginRules(): array
    {
        return [
            'required',
            'email:rfc,dns',
            'exists:users,email',
            function ($attribute, $value, $fail) {
                if (preg_match('/\s/', $value)) {
                    $fail("The $attribute must not contain any spaces.");
                }
            },
        ];
    }

    public static function passwordRules(bool $requireConfirmation = false): array
    {
        $rules = [
            'required',
            'string',
            'min:8',
            'max:20',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/'
        ];

        if ($requireConfirmation) {
            $rules[] = 'confirmed';
        }



        return $rules;
    }

}