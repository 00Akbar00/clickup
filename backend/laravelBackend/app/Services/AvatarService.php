<?php


namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\PngEncoder;
use Laravolt\Avatar\Facade as Avatar;
use Str;

class AvatarService
{
    public function generate(Request $request): ?string
    {
        if ($request->hasFile('profile_picture_url')) {
            $file = $request->file('profile_picture_url');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = $originalName . '_' . Str::uuid() . '.' . $extension;

            return $file->storeAs('avatars', $filename, 'public');
        } else {
            $name = $request->input('fullName', 'User');
            $avatarImage = Avatar::create($name)->getImageObject()->encode(new PngEncoder());
            $filename = 'avatars/' . Str::slug($name) . '_' . Str::uuid() . '.png';

            Storage::disk('public')->put($filename, $avatarImage);
            return $filename;
        }
    }
}
