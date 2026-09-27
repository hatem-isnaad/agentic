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

    public function list(?string $workflowSlug = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $runs = array_values(array_filter(
            $this->runs,
            fn (WorkflowRunRecord $run): bool => ($workflowSlug === null || $run->workflowSlug === $workflowSlug)
                && ($status === null || $run->status === $status),
        ));

        return array_slice(array_reverse($runs), $offset, $limit);
    }

    public function count(?string $workflowSlug = null, ?string $status = null): int
    {
        return count(array_filter(
            $this->runs,
            fn (WorkflowRunRecord $run): bool => ($workflowSlug === null || $run->workflowSlug === $workflowSlug)
                && ($status === null || $run->status === $status),
        ));
    }

    public function deleteOlderThan(\DateTimeInterface $cutoff): int
    {
        unset($cutoff);

        return 0;
    }
}
