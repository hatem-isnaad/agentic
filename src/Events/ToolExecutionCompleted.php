<?php

namespace Agentic\Events;

final class ToolExecutionCompleted
{
    public function __construct(
        public string $tool,
        public mixed $result = null,
        public ?string $agent = null,
    ) {}
}
