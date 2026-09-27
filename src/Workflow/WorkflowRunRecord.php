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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'workflow_slug' => $this->workflowSlug,
            'status' => $this->status,
            'step_pointer' => $this->stepPointer,
            'approval_id' => $this->approvalUuid,
            'variables' => $this->variables,
            'trace' => $this->trace,
            'output' => $this->output,
            'error' => $this->error,
        ];
    }
}
