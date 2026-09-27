<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Workflow\WorkflowRunRecord;

interface WorkflowRunRepository
{
    public function create(string $workflowSlug): WorkflowRunRecord;

    public function find(string $uuid): ?WorkflowRunRecord;

    public function findByApprovalUuid(string $approvalUuid): ?WorkflowRunRecord;

    public function save(WorkflowRunRecord $run): WorkflowRunRecord;
}
