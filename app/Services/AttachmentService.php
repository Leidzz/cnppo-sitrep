<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Sitrep;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    private const DISK = 'sitrep_photos';

    /**
     * Store uploaded photos on the PRIVATE disk and attach rows to the SITREP.
     * - $file->store() generates a random filename — the client never controls
     *   the stored name (blocks path traversal / double-extension tricks)
     * - file_type is read from the UPLOADED CONTENT, not the client's claimed type
     */
    public function storeForSitrep(Sitrep $sitrep, User $user, array $files): void
    {
        DB::transaction(function () use ($sitrep, $user, $files) {
            foreach ($files as $file) {
                /** @var UploadedFile $file */
                $path = $file->store((string) $sitrep->id, self::DISK);

                $sitrep->attachments()->create([
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'uploaded_by' => $user->id,
                    'uploaded_at' => now(),
                ]);
            }
        });
    }

    /**
     * Serve a photo. Only ever called AFTER a policy check on the parent
     * SITREP — private disk + policy + this route = no public URLs (rule 9).
     */
    public function stream(Attachment $attachment)
    {
        abort_unless(Storage::disk(self::DISK)->exists($attachment->file_path), 404);

        return Storage::disk(self::DISK)->response($attachment->file_path);
    }
}
