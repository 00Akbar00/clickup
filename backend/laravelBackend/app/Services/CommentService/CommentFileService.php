<?php
namespace App\Services\CommentService;

use Illuminate\Support\Facades\Storage;

class CommentFileService
{
    public function uploadFiles($files)
    {
        $files = is_array($files) ? $files : [$files];
        $uploaded = [];

        foreach ($files as $file) {
            $path = $file->store('public/comments');

            $uploaded[] = [
                'filename'      => basename($path),
                'originalname'  => $file->getClientOriginalName(),
                'mimetype'      => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'path'          => Storage::url($path),
                'uploadedAt'    => now()->toISOString(),
            ];
        }

        return $uploaded;
    }
}
