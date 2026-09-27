<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Workflow\WorkflowRunRecord;

interface WorkflowRunRepository
{
    public function create(string $workflowSlug): WorkflowRunRecord;

    public function find(string $uuid): ?WorkflowRunRecord;

    public function findByApprovalUuid(string $approvalUuid): ?WorkflowRunRecord;

    public function save(WorkflowRunRecord $run): WorkflowRunRecord;

    /**
     * @return list<WorkflowRunRecord>
     */
    public function list(?string $workflowSlug = null, ?string $status = null, int $limit = 50, int $offset = 0): array;

    public function count(?string $workflowSlug = null, ?string $status = null): int;
}
