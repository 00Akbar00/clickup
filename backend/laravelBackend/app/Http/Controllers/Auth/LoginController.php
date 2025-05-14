<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\TokenService;


class LoginController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $this->tokenService->generateToken($user);

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
