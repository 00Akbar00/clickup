<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class UserService
{
    /**
     * Get formatted profile data for a user.
     *
     * @param  User  $user
     * @return array
     */
    public function getProfileData(User $user): array
    {
        return [
            'fullName' => $user->full_name,
            'email' => $user->email,
            'profile_picture_url' => $user->profile_picture_url ? asset('storage/' . $user->profile_picture_url) : null,
        ];
    }

    /**
     * Update the user's profile information.
     *
     * @param  User  $user
     * @param  array  $data Validated data from the request.
     * @return bool True if the profile was updated, false otherwise.
     * @throws QueryException
     */
    public function updateProfile(User $user, array $data): bool
    {
        $updated = false;

        if (isset($data['fullName']) && $user->full_name !== $data['fullName']) {
            $user->full_name = $data['fullName'];
            $updated = true;
        }

        // Add similar checks for other updatable fields, e.g., email
        // if (isset($data['email']) && $user->email !== $data['email']) {
        //     $user->email = $data['email'];
        //     $updated = true;
        // }

        if ($updated) {
            try {
                $user->save();
            } catch (QueryException $e) {
                Log::error('Database query error during profile update in service.', [
                    'user_id' => $user->id,
                    'error_code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);
                throw $e; // Re-throw to be caught by the controller or global handler
            }
        }
        return $updated;
    }

    /**
     * Update the user's profile picture.
     *
     * @param  User  $user
     * @param  UploadedFile  $profilePictureFile
     * @return string The new profile picture URL.
     * @throws \Exception
     */
    public function updateProfilePicture(User $user, UploadedFile $profilePictureFile): string
    {
        try {
            // Delete old profile picture if exists
            if ($user->profile_picture_url && Storage::disk('public')->exists($user->profile_picture_url)) {
                Storage::disk('public')->delete($user->profile_picture_url);
            }

            $userId = $user->id; // Assuming 'id' is the primary key
            $extension = $profilePictureFile->getClientOriginalExtension();
            $fileName = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;

            // Store the new profile picture
            $path = $profilePictureFile->storeAs('profile_pictures', $fileName, 'public');

            $user->profile_picture_url = $path;
            $user->save();

            return asset('storage/' . $path);
        } catch (QueryException $e) {
            Log::error('Database query error during profile picture update in service.', [
                'user_id' => $user->id,
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            throw $e; // Re-throw
        } catch (\Exception $e) {
            Log::error('File storage or unexpected error during profile picture update in service.', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
            throw $e; // Re-throw
        }
    }
}
