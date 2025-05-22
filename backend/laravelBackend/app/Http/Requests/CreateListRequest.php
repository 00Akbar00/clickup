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
        $nameRules = ValidationService::nameRules();
        $descriptionRules = ValidationService::descriptionRules();
        $listRules = ValidationService::listRules(); 

        return array_merge(
            [
                'name' => $nameRules['rules'],
                'description' => $descriptionRules['rules'],
            ],
            $listRules,
        );
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