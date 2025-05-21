<?php
 
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthService\TokenService;
use Illuminate\Support\Facades\Validator;


class LoginController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function login(Request $request)
    {
        // Validate input using Validator with custom rules and messages
        $validator = $request->validate([
            'email' => ValidationService::emailLoginRules(),
            'password' => 'required|string',
        ]);
    
    
        // Attempt to find user
        $user = User::where('email', $validator['email'])->first();
    
        if (!$user || !Hash::check($validator['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }
    
        // Generate token
        $token = $this->tokenService->generateToken($user);
    
        // Return response
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'full_name' => $user->full_name,
                'email' => $user->email,
                'profile_picture_url' => asset('storage/' . $user->profile_picture_url),
            ]
        ]);
    }
}
