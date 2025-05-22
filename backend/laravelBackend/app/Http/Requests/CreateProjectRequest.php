<?php

namespace App\Http\Requests;

use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nameRules = ValidationService::nameRules();
        $descriptionRules = ValidationService::descriptionRules();
        $projectRules = ValidationService::projectRules(); 
    
        return array_merge(
            [
                'name' => $nameRules['rules'],
                'description' => $descriptionRules['rules'],
            ],
            $projectRules 
        );
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name must not exceed 25 characters.',
            'description.max' => 'The description must not exceed 255 characters.',
            'visibility.in' => 'The visibility must be either public or private.',
            'color_code.max' => 'The color code must not exceed 7 characters.',
        ];
    }
}
