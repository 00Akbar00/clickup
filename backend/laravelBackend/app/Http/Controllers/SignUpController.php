<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AvatarService;
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
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'profile_picture_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $avatarPath = $this->avatarService->generate($request);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'fullName' => $request->fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_picture_url' => $avatarPath,
        ]);

        return response()->json(['message' => 'User registered', 'user' => $user], 201);
    }
}
