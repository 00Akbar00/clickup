<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\VerifyValidationService\ValidationService;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Keep true if checking auth in controller
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
            'name.max' => 'The workspace name may not be greater than 25 characters.',
            'description.max' => 'The description may not be greater than 255 characters.',
            'logo.max' => 'The logo must not be larger than 2MB.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, jpg, png.',
        ];
    }
}

