<?php

namespace Agentic\Workflow;

final readonly class WorkflowRunRecord
{
    /**
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $trace
     */
    public function __construct(
        public string $uuid,
        public string $workflowSlug,
        public string $status,
        public int $stepPointer,
        public array $variables,
        public array $trace,
        public ?string $approvalUuid = null,
        public ?array $output = null,
        public ?string $error = null,
    ) {}
}
