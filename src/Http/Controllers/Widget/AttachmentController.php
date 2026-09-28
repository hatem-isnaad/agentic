<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\Services\WidgetAttachmentService;
use Agentic\Widget\Services\WidgetConversationService;
use Agentic\Widget\Support\WidgetFileSignature;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AttachmentController
{
    public function __construct(
        private WidgetAttachmentService $attachments,
        private WidgetConversationService $conversations,
    ) {}

    public function show(Request $request, string $id, string $file): BinaryFileResponse
    {
        $signed = WidgetFileSignature::isValid($request);
        if (! $signed) {
            $this->conversations->assertVisible($id, WidgetIdentity::fromRequest($request));
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
            'Cache-Control' => $signed ? 'private, max-age=3600' : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
