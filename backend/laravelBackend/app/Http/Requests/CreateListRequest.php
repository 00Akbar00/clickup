<?php

namespace App\Http\Requests;

use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class CreateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ValidationService::nameRules(),
            'description' => ValidationService::descriptionRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name must not exceed 25 characters.',
            'description.max' => 'The description must not exceed 255 characters.',
        ];
    }
}