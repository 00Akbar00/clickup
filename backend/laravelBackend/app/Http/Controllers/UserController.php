<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserProfilePictureRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    protected UserService $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        // Apply auth middleware to all methods in this controller
        // Or apply to specific methods using ->only() or ->except()
        // $this->middleware('auth:api'); // Assuming you are using API authentication
    }

    /**
     * Get the authenticated user's profile.
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();

        // This check might be redundant if auth middleware is properly configured
        // and FormRequest authorize() methods are used.
        // However, it's a good explicit check if middleware isn't guaranteed for all routes.
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $profileData = $this->userService->getProfileData($user);

        return response()->json(['user' => $profileData]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param UpdateUserProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = Auth::user(); // User is guaranteed to be authenticated by UpdateUserProfileRequest's authorize method

        try {
            // $request->validated() will only return data that passed validation rules
            // and was defined in the rules() method of UpdateUserProfileRequest.
            $updated = $this->userService->updateProfile($user, $request->validated());

            if ($updated) {
                return response()->json(['message' => 'Profile updated successfully.']);
            }

            return response()->json(['message' => 'No changes were made to the profile.']);

        } catch (QueryException $e) {
            // Logging is already done in the service, but you can add controller-specific context if needed.
            // Log::error('Controller: Database query error during profile update.', ['user_id' => $user->id]);
            return response()->json(['message' => 'A server error occurred while updating your profile. Please try again later.'], 500);
        } catch (\Exception $e) {
            // Logging is already done in the service.
            // Log::error('Controller: Unexpected error during profile update.', ['user_id' => $user->id]);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    /**
     * Update the authenticated user's profile picture.
     * Allowed types: jpeg, png, jpg, gif, webp. Max size: 5MB.
     *
     * @param UpdateUserProfilePictureRequest $request
     * @return JsonResponse
     */
    public function updateProfilePicture(UpdateUserProfilePictureRequest $request): JsonResponse
    {
        $user = Auth::user(); // User is guaranteed to be authenticated

        try {
            $newProfilePictureUrl = $this->userService->updateProfilePicture(
                $user,
                $request->file('profile_picture')
            );

            return response()->json([
                'message' => 'Profile picture updated successfully. 🖼️',
                'profile_picture_url' => $newProfilePictureUrl,
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'A server error occurred while updating your profile picture. Please try again later.'], 500);
        } catch (\Exception $e) {
            // This can catch file system errors or other issues from the service.
            return response()->json(['message' => 'An unexpected error occurred while updating your profile picture.'], 500);
        }
    }
}
