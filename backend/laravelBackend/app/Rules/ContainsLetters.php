<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContainsLetters implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/[a-zA-Z]/', $value)) {
            $fail('The ' .$attribute. ' must contain at least one letter.');
        }
    }
}