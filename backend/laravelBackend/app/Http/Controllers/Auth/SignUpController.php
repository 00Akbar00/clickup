<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Services\AuthService\AvatarService;
use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Str;


class SignupController extends Controller
{
    protected $avatarService;

    public function __construct(AvatarService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    public function register(Request $request)
    {
        // Get input and trim full_name ONLY
        $input = $request->all();

        // Get individual rule/message sets
        $nameRules = ValidationService::nameRules();
        $emailValidation = ValidationService::emailSignupRules();
        $passwordValidation = ValidationService::passwordRules(true);
        
        // Combine all rules (removed avatar rules since they're now in AvatarService)
        $rules = [
            'full_name' => $nameRules['rules'],
            'email' => $emailValidation['rules'],
            'password' => $passwordValidation['rules'],
        ];
        // Combine all messages
        $messages = array_merge(
            $nameRules['messages'],
            $emailValidation['messages'],
            $passwordValidation['messages']
        );
        // Validate the request (excluding avatar validation)
        $request->validate($rules, $messages);

        try {
            $avatarPath = $this->avatarService->generate($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Avatar validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $user = User::create([
            'user_id' => (string) Str::uuid(),
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'profile_picture_url' => asset('storage/' . $avatarPath),
        ]);

        return response()->json(['message' => 'User registered', 'user' => $user], 201);
    }
}