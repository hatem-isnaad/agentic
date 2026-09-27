<?php

namespace Agentic\Events;

final class ToolExecutionFailed
{
    public function __construct(
        public string $tool,
        public string $error,
        public ?string $agent = null,
    ) {}
}
