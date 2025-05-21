<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNameCharacters implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $value)) {
            $fail('The ' .$attribute. ' may only contain letters, numbers, spaces, or hyphens.');
        }
    }
}