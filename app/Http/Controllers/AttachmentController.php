<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Gate;

class AttachmentController extends Controller
{
    /**
     * Photos are served ONLY here — never via public URLs (rule 9).
     * Authorization is against the PARENT SITREP: if you may view the
     * report, you may see its photos.
     */
    public function show(Attachment $attachment, AttachmentService $service)
    {
        Gate::authorize('view', $attachment->sitrep);

        return $service->stream($attachment);
    }
}
