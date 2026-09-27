<?php

namespace Agentic\Tool;

use Agentic\Context\RuntimeContext;
use Agentic\Execution\AgentExecutionContext;

final readonly class ToolExecutionContext
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $arguments = [],
        public array $metadata = [],
        public ?AgentExecutionContext $execution = null,
        public ?RuntimeContext $runtime = null,
    ) {}

    public function runtime(): RuntimeContext
    {
        return $this->runtime
            ?? $this->execution?->runtime()
            ?? new RuntimeContext();
    }
}
