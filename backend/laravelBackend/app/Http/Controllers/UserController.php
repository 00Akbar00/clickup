<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function getProfile()
    {
        $user = Auth::user();

        return response()->json([
            'user' => [
                'fullName' => $user->full_name,
                'email' => $user->email,
                'profile_picture_url' => asset('storage/' . $user->profile_picture_url),
            ]
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request){
        $user = Auth::user();

        $request->validate([
        'fullName' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
        ]);;

        if ($request->filled('fullName')) {
        $user->full_name = $request->input('fullName');
        }

        if ($request->filled('email')) {
        $user->email = $request->input('email');
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
}



    /**
     * Update the authenticated user's profile picture.
     */
    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Delete old profile picture if exists
        if ($user->profile_picture_url && Storage::disk('public')->exists($user->profile_picture_url)) {
            Storage::disk('public')->delete($user->profile_picture_url);
        }

        // Store new profile picture
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $user->profile_picture_url = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'profile_picture_url' => asset('storage/' . $path),
        ]);
    }
}
