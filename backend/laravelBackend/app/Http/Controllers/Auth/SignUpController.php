<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Services\AuthService\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'profile_picture_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $avatarPath = $this->avatarService->generate($request);

        $user = User::create([
            'user_id' => (string) Str::uuid(),
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_picture_url' => asset('storage/' . $avatarPath),
        ]);

        return response()->json(['message' => 'User registered', 'user' => $user], 201);
    }
}
