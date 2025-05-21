<?php
namespace App\Services\CommentService;

use Illuminate\Support\Facades\Storage;
use Str;

class CommentFileService
{
    public function uploadFiles($files)
    {
        $files = is_array($files) ? $files : [$files];
        $uploaded = [];

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            $uuidFilename = Str::uuid() . '.' . $extension;
            $path = $file->storeAs('public/comments', $uuidFilename);

            $uploaded[] = [
                'filename' => basename($path),
                'originalname' => $file->getClientOriginalName(),
                'mimetype' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => Storage::url($path),
                'uploadedAt' => now()->toISOString(),
            ];
        }

        return $uploaded;
    }
}
