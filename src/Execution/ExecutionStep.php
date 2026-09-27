<?php

namespace Agentic\Execution;

final class ExecutionStep
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $executionId,
        public string $type,
        public ExecutionStatus $status = ExecutionStatus::Pending,
        public array $input = [],
        public mixed $output = null,
        public array $metadata = [],
        public ?string $startedAt = null,
        public ?string $completedAt = null,
    ) {}
}
