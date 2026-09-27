<?php

namespace Agentic\Execution;

use Agentic\Context\RuntimeContext;
use Agentic\Conversation\Conversation;

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
        public ?Conversation $conversation = null,
        public ?string $conversationId = null,
        public ?string $executionId = null,
    ) {}

    public function runtime(): RuntimeContext
    {
        $runtime = $this->runtime ?? new RuntimeContext();

        if ($this->conversation !== null && ! $runtime->has('conversation')) {
            $runtime = $runtime->with('conversation', $this->conversation);
        }

        return $runtime;
    }
}
