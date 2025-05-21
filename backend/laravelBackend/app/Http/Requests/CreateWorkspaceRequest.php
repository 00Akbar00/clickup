<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\VerifyValidationService\ValidationService;

class CreateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ValidationService::workspaceRules();
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The workspace name is required.',
            'name.max' => 'The workspace name must not exceed 25 characters.',
            'logo.max' => 'The logo must not be larger than 2MB.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, jpg, png.',
        ];
    }
}
