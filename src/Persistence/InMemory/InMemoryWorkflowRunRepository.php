<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\WorkflowRunRepository;
use Agentic\Workflow\WorkflowRunRecord;
use Illuminate\Support\Str;

final class InMemoryWorkflowRunRepository implements WorkflowRunRepository
{
    /** @var array<string, WorkflowRunRecord> */
    private array $runs = [];

    public function create(string $workflowSlug): WorkflowRunRecord
    {
        $run = new WorkflowRunRecord(
            uuid: (string) Str::uuid(),
            workflowSlug: $workflowSlug,
            status: 'running',
            stepPointer: 0,
            variables: ['input' => []],
            trace: [],
        );

        $this->runs[$run->uuid] = $run;

        return $run;
    }

    public function find(string $uuid): ?WorkflowRunRecord
    {
        return $this->runs[$uuid] ?? null;
    }

    public function findByApprovalUuid(string $approvalUuid): ?WorkflowRunRecord
    {
        foreach ($this->runs as $run) {
            if ($run->approvalUuid === $approvalUuid) {
                return $run;
            }
        }

        return null;
    }

    public function save(WorkflowRunRecord $run): WorkflowRunRecord
    {
        $this->runs[$run->uuid] = $run;

        return $run;
    }
}
