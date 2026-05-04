<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(Model $attachable, array $files, ?User $user = null): void
    {
        foreach ($files as $file) {
            $path = $file->store('attachments', 'public');

            $attachable->attachments()->create([
                'user_id' => $user?->id,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }
}
