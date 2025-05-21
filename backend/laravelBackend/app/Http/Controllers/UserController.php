<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // <<< CORRECTED: Ensure this line is exactly like this
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function getProfile()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'user' => [
                'fullName' => $user->full_name, // Assuming your User model has 'full_name'
                'email' => $user->email,
                'profile_picture_url' => $user->profile_picture_url ? asset('storage/' . $user->profile_picture_url) : null,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $messages = [
                'fullName.required' => 'The full name cannot be empty if you intend to change it.',
                'fullName.string'   => 'The full name must be a text value. Please provide a valid name.',
                'fullName.min'      => 'The full name must be at least :min characters long.',
                'fullName.max'      => 'The full name cannot be longer than :max characters.',
                'fullName.regex'    => 'The full name contains invalid characters. Only letters, spaces, hyphens (-), and apostrophes (\') are allowed.',
            ];


            $validatedData = $request->validate([
                'fullName' => [
                    'sometimes', // Validate 'fullName' only if it is present in the request.
                    'required',  // If 'fullName' is present, it must not be empty.
                    'string',    // Must be a string.
                    'min:2',     // Minimum 2 characters.
                    'max:255',   // Maximum 255 characters.
                    'regex:/^[\p{L}\s\'\-]+$/u', // Allows Unicode letters, spaces, apostrophes, hyphens.
                ],
            ], $messages); // Pass your custom messages to the validator.

            $updated = false;

            // Update fullName if it was provided, validated, and is different from the current name
            if ($request->filled('fullName')) {
                if ($user->full_name !== $validatedData['fullName']) {
                     $user->full_name = $validatedData['fullName'];
                     $updated = true;
                }
            }


            if ($updated) {
                $user->save(); // Perform the database operation
                return response()->json(['message' => 'Profile updated successfully.']);
            }

            // If no fields eligible for update were changed
            return response()->json(['message' => 'No changes were made to the profile.']);

        } catch (ValidationException $e) {
            Log::warning('Validation failed during profile update: ' . $e->getMessage(), [
                'user_id' => $user ? $user->id : 'Unauthenticated attempt', // Avoid error if $user is null
                'errors' => $e->errors()
            ]);


            throw $e;

        } catch (QueryException $e) {
            // Handle database-specific errors (e.g., connection issues, constraint violations)
            Log::error('Database query error during profile update.', [
                'user_id' => $user ? $user->id : 'Unknown user',
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
                // 'trace' => $e->getTraceAsString() // Optional: for detailed debugging
            ]);
            return response()->json(['message' => 'A server error occurred while updating your profile. Please try again later.'], 500);

        } catch (\Exception $e) {
            // Handle any other unexpected errors
            Log::error('Unexpected error during profile update.', [
                'user_id' => $user ? $user->id : 'Unknown user',
                'message' => $e->getMessage(),
                // 'trace' => $e->getTraceAsString() // Optional: for detailed debugging
            ]);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    /**
     * Update the authenticated user's profile picture.
     * Allowed types: jpeg, png, jpg, gif, webp. Max size: 5MB.
     */
    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'profile_picture' => [
                'required',
                'image', // Checks if the file is an image (jpeg, png, bmp, gif, svg, or webp)
                'mimes:jpeg,png,jpg,gif,webp', // Specify allowed image MIME types
                'max:5120', // Max file size in kilobytes (5MB = 5 * 1024 KB)
            ],
        ]);

        // Delete old profile picture if exists
        if ($user->profile_picture_url && Storage::disk('public')->exists($user->profile_picture_url)) {
            Storage::disk('public')->delete($user->profile_picture_url);
        }


        $userId = $user->user_id ?? $user->id; // Handle cases where 'user_id' might not be the PK name
        $extension = $request->file('profile_picture')->getClientOriginalExtension();
        $fileName = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;

        $path = $request->file('profile_picture')->storeAs('profile_pictures', $fileName, 'public');

        $user->profile_picture_url = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture updated successfully. 🖼️',
            'profile_picture_url' => asset('storage/' . $path),
        ]);
    }
}
