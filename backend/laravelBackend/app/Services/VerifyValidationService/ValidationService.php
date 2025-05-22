<?php

namespace App\Services\VerifyValidationService;

use App\Rules\ContainsLetters;
use App\Rules\ValidDescriptionCharacters;
use App\Rules\ValidNameCharacters;
use Illuminate\Validation\Rule;

class ValidationService
{
    public static function nameRules(): array
    {
        $rules = [
            'required',
            'string',
            'max:25',
            new ContainsLetters,
            new ValidNameCharacters,
        ];

        $messages = [
            'full_name.required' => 'The full name is required.',
            'full_name.max' => 'The full name must not exceed 25 characters.',
            'title.required' => 'The full name is required.',
            'title.max' => 'The full name must not exceed 25 characters.',

        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public static function descriptionRules(): array
    {
        return [
            'rules' => [
                'nullable',
                'string',
                'max:255',
                new ValidDescriptionCharacters,
            ],
            'messages' => [
                'description.max' => 'The description must not exceed 255 characters.',
            ]
        ];
    }

    public static function projectRules(bool $includeAdditionalFields = true): array
    {
        $rules = [];
        $rules['visibility'] = ['nullable', 'string', Rule::in(['public', 'private'])];
        $rules['color_code'] = ['nullable', 'string', 'max:10', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return $rules;
    }
    public static function listRules(bool $includeAdditionalFields = true): array
    {
        $rules = [];
        $rules['status'] = ['nullable', 'string', Rule::in(['active', 'archived'])];

        return $rules;
    }
    public static function taskRules(): array
    {
        $nameRules = self::nameRules();
        $descriptionRules = self::descriptionRules();
    
        return [
            'rules' => [
                'title' => $nameRules['rules'],
                'description' => $descriptionRules['rules'],
                'due_date' => [
                    'nullable',
                    'regex:/^\d{2}-\d{2}-\d{4}$/', // Match dd-mm-yyyy format
                    function ($attribute, $value, $fail) {
                        $date = \DateTime::createFromFormat('d-m-Y', $value);
                        $now = new \DateTime('today');
    
                        if (!$date || $date->format('d-m-Y') !== $value) {
                            $fail('The due date must be in the format dd-mm-yyyy.');
                        } elseif ($date < $now) {
                            $fail('The due date cannot be in the past.');
                        }
                    },
                ],
                'priority' => ['nullable', Rule::in(['high', 'normal', 'low', 'clear'])],
                'status' => ['nullable', Rule::in(['todo', 'inprogress', 'completed'])],
            ],
            'messages' => array_merge(
                $nameRules['messages'],
                $descriptionRules['messages'],
                [
                    'due_date.regex' => 'The due date must be in the format dd-mm-yyyy.',
                    'priority.in' => 'Priority must be one of: high, normal, low, or clear.',
                    'status.in' => 'Status must be one of: todo, inprogress, or completed.',
                ]
            ),
        ];
    }

    public static function emailSignupRules(): array
    {
        $rules = [
            'required',
            'email:rfc,dns',
            'unique:users,email',
            function ($attribute, $value, $fail) {
                if (preg_match('/\s/', $value)) {
                    $fail("The $attribute must not contain any spaces.");
                }
            },
        ];

        $messages = [
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public static function emailLoginRules(): array
    {
        return [
            'rules' => [
                'email' => [
                    'required',
                    'email:rfc,dns',
                    'exists:users,email',
                    function ($attribute, $value, $fail) {
                        if (preg_match('/\s/', $value)) {
                            $fail("The $attribute must not contain any spaces.");
                        }
                    },
                ],
            ],
            'messages' => [
                'email.required' => 'The email field is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.exists' => 'We could not find a user with that email address.',
            ]
        ];

    }



    public static function passwordRules(bool $requireConfirmation = false): array
    {
        $rules = [
            'required',
            'string',
            'min:8',
            'max:20',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/'
        ];

        if ($requireConfirmation) {
            $rules['password'][] = 'confirmed';
        }

        $messages = [
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.max' => 'The password must not be more than 20 characters.',
            'password.regex' => 'The password must include at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }

    public static function avatarRules(): array
    {
        $rules = [
            'profile_picture_url' => [
                'nullable',
                'file',
                'max:1024',
                'mimetypes:image/jpeg,image/png',
            ],
        ];

        $messages = [
            'profile_picture_url.file' => 'The avatar must be a valid file.',
            'profile_picture_url.mimetypes' => 'The avatar must be a JPEG, JPG, or PNG image.',
            'profile_picture_url.max' => 'The avatar size must not exceed 2 MB.',
        ];

        return ['rules' => $rules, 'messages' => $messages];
    }



    public static function workspaceRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:25',
                new ContainsLetters,
                new ValidNameCharacters,
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
                new ValidDescriptionCharacters,
            ],
            // 'logo_url' => [
            //     'nullable',
            //     'file',
            //     'mimes:jpeg,jpg,png',
            //     'max:1024'
            // ], // 2MB max
        ];

    }

    // File: app/Services/VerifyValidationService/ValidationService.php

    public static function updateWorkspaceMemberRoleRules(): array
    {
        return [
            'rules' => [
                'workspace_id' => ['required', 'uuid', 'exists:workspaces,workspace_id'],
                'member_id' => ['required', 'uuid', 'exists:workspace_members,workspace_member_id'],
                'role' => ['required', Rule::in(['owner', 'member'])],
            ],
            'messages' => [
                'workspace_id.required' => 'Workspace ID is required.',
                'workspace_id.uuid' => 'Workspace ID must be a valid UUID.',
                'workspace_id.exists' => 'Workspace not found.',

                'member_id.required' => 'Member ID is required.',
                'member_id.uuid' => 'Member ID must be a valid UUID.',
                'member_id.exists' => 'Member not found in the workspace.',

                'role.required' => 'Role is required.',
                'role.in' => 'Role must be either owner or member.',
            ]
        ];
    }

    public static function teamRules(): array
    {
        return [
            'rules' => [
                'name' => [
                    'required',
                    'string',
                    'max:25',
                    new ContainsLetters,
                    new ValidNameCharacters,
                ],
                'description' => [
                    'nullable',
                    'string',
                    'max:255',
                    new ValidDescriptionCharacters,
                ],
                'visibility' => [
                    'nullable',
                    'string',
                    Rule::in(['public', 'private']),
                ],
                'color_code' => [
                    'nullable',
                    'string',
                    'max:10',
                    'regex:/^#[0-9A-Fa-f]{6}$/', // optional hex color code validation
                ],
            ],
            'messages' => [
                'name.required' => 'Team name is required.',
                'name.string' => 'Team name must be a string.',
                'name.max' => 'Team name must not exceed 255 characters.',

                'description.string' => 'Description must be a string.',
                'description.max' => 'Description must not exceed 255 characters.',

                'visibility.in' => 'Visibility must be either public or private.',

                'color_code.max' => 'Color code must not exceed 10 characters.',
                'color_code.regex' => 'Color code must be a valid hex (e.g. #FF5733).',
            ],
        ];
    }

    public static function taskAssigneeRules(): array
    {
        return [
            'rules' => [
                'team_member_id' => 'required|array',
                'team_member_id.*' => 'uuid|exists:team_members,team_member_id',
            ],
            'messages' => [
                'team_member_id.required' => 'At least one team member must be specified',
                'team_member_id.*.uuid' => 'Invalid team member ID format',
                'team_member_id.*.exists' => 'One or more team members not found',
            ]
        ];
    }

    public static function workspaceInvitationRules(): array
    {
        return [
            'rules' => [
                'emails' => ['required', 'array', 'min:1'],
                'emails.*' => ['required', 'email:rfc,dns'],
                'role' => ['required', Rule::in(['member', 'admin'])],
            ],
            'messages' => [
                'emails.required' => 'At least one email address is required.',
                'emails.array' => 'Emails must be an array.',
                'emails.min' => 'Please provide at least one email.',
                'emails.*.required' => 'Each email address is required.',
                'emails.*.email' => 'Each email must be a valid email address.',
                'role.required' => 'Role is required.',
                'role.in' => 'Role must be either member or admin.',
            ],
        ];
    }



}

