<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\VerifyValidationService\ValidationService;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return ValidationService::taskRules()['rules'];
    }

    public function messages(): array
    {
        return ValidationService::taskRules()['messages'];
    }
}
