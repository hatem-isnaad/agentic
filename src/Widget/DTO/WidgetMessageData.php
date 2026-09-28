<?php

namespace Agentic\Widget\DTO;

use Illuminate\Http\UploadedFile;

final readonly class WidgetMessageData
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<UploadedFile>  $files
     */
    public function __construct(
        public string $agent,
        public string $message,
        public WidgetIdentity $identity,
        public ?string $conversationId = null,
        public array $metadata = [],
        public array $files = [],
    ) {}
}
