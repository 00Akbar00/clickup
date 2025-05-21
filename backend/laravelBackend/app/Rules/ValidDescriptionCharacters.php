<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDescriptionCharacters implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/[<>@#$%^&*{}\[\]|\'\\\\\/]/', $value)) {
            $fail('The ' . $attribute . ' contains invalid characters: < > @ # $ % ^ & * { } [ ] | \' \\ /');
        }
    }

}
