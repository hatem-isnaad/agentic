<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\WorkflowRunRepository;
use Agentic\Models\WorkflowRun;
use Agentic\Workflow\WorkflowRunRecord;
use Illuminate\Support\Str;

final class EloquentWorkflowRunRepository implements WorkflowRunRepository
{
    public function create(string $workflowSlug): WorkflowRunRecord
    {
        $model = WorkflowRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'workflow_slug' => $workflowSlug,
            'status' => 'running',
            'step_pointer' => 0,
            'variables' => ['input' => []],
            'trace' => [],
        ]);

        return $this->toRecord($model);
    }

    public function find(string $uuid): ?WorkflowRunRecord
    {
        $model = WorkflowRun::query()->where('uuid', $uuid)->first();

        return $model ? $this->toRecord($model) : null;
    }

    public function findByApprovalUuid(string $approvalUuid): ?WorkflowRunRecord
    {
        $model = WorkflowRun::query()->where('approval_uuid', $approvalUuid)->first();

        return $model ? $this->toRecord($model) : null;
    }

    public function save(WorkflowRunRecord $run): WorkflowRunRecord
    {
        $model = WorkflowRun::query()->where('uuid', $run->uuid)->firstOrFail();

        $model->fill([
            'workflow_slug' => $run->workflowSlug,
            'status' => $run->status,
            'step_pointer' => $run->stepPointer,
            'variables' => $run->variables,
            'trace' => $run->trace,
            'approval_uuid' => $run->approvalUuid,
            'output' => $run->output,
            'error' => $run->error,
        ])->save();

        return $this->toRecord($model->fresh());
    }

    public function list(?string $workflowSlug = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $query = WorkflowRun::query()->orderByDesc('updated_at');

        if ($workflowSlug !== null && $workflowSlug !== '') {
            $query->where('workflow_slug', $workflowSlug);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query
            ->offset(max(0, $offset))
            ->limit(min(100, max(1, $limit)))
            ->get()
            ->map(fn (WorkflowRun $model) => $this->toRecord($model))
            ->all();
    }

    public function count(?string $workflowSlug = null, ?string $status = null): int
    {
        $query = WorkflowRun::query();

        if ($workflowSlug !== null && $workflowSlug !== '') {
            $query->where('workflow_slug', $workflowSlug);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query->count();
    }

    public function deleteOlderThan(\DateTimeInterface $cutoff): int
    {
        return WorkflowRun::query()
            ->where('updated_at', '<', $cutoff)
            ->delete();
    }

    private function toRecord(WorkflowRun $model): WorkflowRunRecord
    {
        return new WorkflowRunRecord(
            uuid: $model->uuid,
            workflowSlug: $model->workflow_slug,
            status: $model->status,
            stepPointer: (int) $model->step_pointer,
            variables: is_array($model->variables) ? $model->variables : [],
            trace: is_array($model->trace) ? $model->trace : [],
            approvalUuid: $model->approval_uuid,
            output: is_array($model->output) ? $model->output : null,
            error: $model->error,
        );
    }
}
