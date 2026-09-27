<?php

namespace Agentic\Execution;

final class ExecutionStep
{
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
        public ?int $toolId = null,
        public ?int $toolVersionId = null,
        public ?bool $permissionAllowed = null,
        public ?int $durationMs = null,
    ) {}
}
