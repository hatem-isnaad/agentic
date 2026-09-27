<?php

namespace Agentic\Execution;

/**
 * Runtime representation of an Agent execution lifecycle.
 */
final class Execution
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
     * @param  list<ExecutionStep>  $steps
     */
    public function __construct(
        public string $id,
        public string $agent,
        public ExecutionStatus $status = ExecutionStatus::Pending,
        public array $input = [],
        public mixed $output = null,
        public ?string $error = null,
        public ?string $conversationId = null,
        public array $metadata = [],
        public array $steps = [],
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public ?string $failedAt = null,
    ) {}
}
