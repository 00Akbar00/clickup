<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Services\AuthService\AvatarService;
use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
        // $input['full_name'] = isset($input['full_name']) ? trim($input['full_name']) : null;

        $request->validate([
            'full_name' => ValidationService::nameRules(),
            'email' => ValidationService::emailSignupRules(),
            'password' => ValidationService::passwordRules(true),
        ]);

        $avatarPath = $this->avatarService->generate($request);


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
