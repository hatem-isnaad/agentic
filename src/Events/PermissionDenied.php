<?php

namespace Agentic\Events;

final class PermissionDenied
{
    public function __construct(
        public string $tool,
        public string $message,
        public ?string $agent = null,
    ) {}
}
