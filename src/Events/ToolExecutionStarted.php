<?php

namespace Agentic\Events;

final class ToolExecutionStarted
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public string $tool,
        public array $arguments = [],
        public ?string $agent = null,
    ) {}
}
