<?php

// app/Http/Requests/SendWorkspaceInvitationsRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\VerifyValidationService\ValidationService;

class AddWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ValidationService::workspaceInvitationRules()['rules'];
    }

    public function messages(): array
    {
        return ValidationService::workspaceInvitationRules()['messages'];
    }
}
