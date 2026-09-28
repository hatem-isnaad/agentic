<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ConversationAdminService;
use Agentic\Widget\Services\WidgetAttachmentService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ConversationAttachmentController
{
    public function __construct(
        private ConversationAdminService $conversations,
        private WidgetAttachmentService $attachments,
    ) {}

    public function show(string $id, string $file): BinaryFileResponse
    {
        if ($this->conversations->find($id) === null) {
            abort(404);
        }

        $absolute = $this->attachments->absolutePath($id, $file);
        if ($absolute === null) {
            abort(404);
        }

        $mime = $this->attachments->mime($absolute);
        $disposition = str_starts_with($mime, 'image/') ? 'inline' : 'attachment';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.basename($file).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
