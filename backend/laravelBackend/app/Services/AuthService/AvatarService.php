<?php


namespace App\Services\AuthService;

use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\PngEncoder;
use Laravolt\Avatar\Facade as Avatar;
use Str;

class AvatarService
{
    public function generate(Request $request): ?string
    {


        if ($request->hasFile('profile_picture_url')) {
            // $validateAvatarFile($request->file('profile_picture_url'));
            $avatarRules = ValidationService::avatarRules();

            // Validate the file
            $validator = Validator::make(
                ['profile_picture_url' => $request->file('profile_picture_url')],
                $avatarRules['rules'],
                $avatarRules['messages']
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $file = $request->file('profile_picture_url');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = $originalName . '_' . Str::uuid() . '.' . $extension;

            return $file->storeAs('avatars', $filename, 'public');
        } else {
            $name = $request->input('full_name', 'User');
            $avatarImage = Avatar::create($name)->getImageObject()->encode(new PngEncoder());

            $filename = 'avatars/' . Str::slug($name) . '_' . Str::uuid() . '.png';

            Storage::disk('public')->put($filename, $avatarImage);
            return $filename;
        }
    }
}
