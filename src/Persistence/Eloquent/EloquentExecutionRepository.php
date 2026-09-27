<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Execution\Execution as ExecutionDto;
use Agentic\Execution\ExecutionStatus;
use Agentic\Execution\ExecutionStep as ExecutionStepDto;
use Agentic\Models\Execution;
use Agentic\Models\ExecutionStep;
use Illuminate\Support\Str;

final class EloquentExecutionRepository implements ExecutionRepository
{
    public function store(ExecutionDto $execution): ExecutionDto
    {
        $model = Execution::query()->create([
            'uuid' => $execution->id,
            'agent' => $execution->agent,
            'status' => $execution->status,
            'input' => $execution->input,
            'output' => is_array($execution->output) ? $execution->output : ['value' => $execution->output],
            'error' => $execution->error,
            'conversation_id' => $execution->conversationId,
            'metadata' => $execution->metadata,
            'started_at' => $execution->startedAt,
            'completed_at' => $execution->completedAt,
            'failed_at' => $execution->failedAt,
        ]);

        foreach ($execution->steps as $step) {
            $this->persistStep($model->id, $step);
        }

        return $this->toDto($model->fresh('steps'));
    }

    public function find(string $id): ?ExecutionDto
    {
        $model = Execution::query()->with('steps')->where('uuid', $id)->first();

        return $model ? $this->toDto($model) : null;
    }

    public function update(ExecutionDto $execution): ExecutionDto
    {
        $model = Execution::query()->where('uuid', $execution->id)->firstOrFail();

        $model->fill([
            'status' => $execution->status,
            'input' => $execution->input,
            'output' => is_array($execution->output) ? $execution->output : ['value' => $execution->output],
            'error' => $execution->error,
            'conversation_id' => $execution->conversationId,
            'metadata' => $execution->metadata,
            'started_at' => $execution->startedAt,
            'completed_at' => $execution->completedAt,
            'failed_at' => $execution->failedAt,
        ])->save();

        // Replace steps for simplicity.
        $model->steps()->delete();
        foreach ($execution->steps as $step) {
            $this->persistStep($model->id, $step);
        }

        return $this->toDto($model->fresh('steps'));
    }

    private function persistStep(int $executionId, ExecutionStepDto $step): void
    {
        ExecutionStep::query()->create([
            'execution_id' => $executionId,
            'tool_id' => $step->toolId,
            'tool_version_id' => $step->toolVersionId,
            'uuid' => $step->id !== '' ? $step->id : (string) Str::uuid(),
            'type' => $step->type,
            'status' => $step->status,
            'input' => $step->input,
            'output' => is_array($step->output) ? $step->output : ['value' => $step->output],
            'metadata' => $step->metadata,
            'started_at' => $step->startedAt,
            'completed_at' => $step->completedAt,
            'permission_allowed' => $step->permissionAllowed,
            'duration_ms' => $step->durationMs,
        ]);
    }

    private function toDto(Execution $model): ExecutionDto
    {
        return new ExecutionDto(
            id: $model->uuid,
            agent: $model->agent,
            status: $model->status instanceof ExecutionStatus ? $model->status : ExecutionStatus::from((string) $model->status),
            input: $model->input ?? [],
            output: $model->output,
            error: $model->error,
            conversationId: $model->conversation_id,
            metadata: $model->metadata ?? [],
            steps: $model->steps->map(fn (ExecutionStep $step) => new ExecutionStepDto(
                id: $step->uuid,
                executionId: $model->uuid,
                type: $step->type,
                status: $step->status instanceof ExecutionStatus ? $step->status : ExecutionStatus::from((string) $step->status),
                input: $step->input ?? [],
                output: $step->output,
                metadata: $step->metadata ?? [],
                startedAt: optional($step->started_at)?->toISOString(),
                completedAt: optional($step->completed_at)?->toISOString(),
                toolId: $step->tool_id,
                toolVersionId: $step->tool_version_id,
                permissionAllowed: $step->permission_allowed,
                durationMs: $step->duration_ms,
            ))->all(),
            startedAt: optional($model->started_at)?->toISOString(),
            completedAt: optional($model->completed_at)?->toISOString(),
            failedAt: optional($model->failed_at)?->toISOString(),
        );
    }
}
