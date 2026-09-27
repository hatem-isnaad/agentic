<?php

namespace Agentic\Execution;

use Agentic\Context\RuntimeContext;

/**
 * Input for a single AgentRuntime invocation.
 */
final readonly class AgentExecutionContext
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $variables
     * @param  list<mixed>  $messages  Prior conversation messages for Laravel AI SDK
     */
    public function __construct(
        public string $message,
        public array $metadata = [],
        public array $variables = [],
        public array $messages = [],
        public ?RuntimeContext $runtime = null,
    ) {}

    public function runtime(): RuntimeContext
    {
        return $this->runtime ?? new RuntimeContext();
    }
}
