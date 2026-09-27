<?php

namespace Agentic\Execution;

use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Events\AgentExecutionCompleted;
use Agentic\Events\AgentExecutionFailed;
use Agentic\Events\AgentExecutionStarted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * Tracks Agent execution lifecycle without coupling Runtime to Eloquent.
 */
final class ExecutionManager
{
    public function __construct(
        private ExecutionRepository $repository,
        private ?Dispatcher $events = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
     */
    public function start(string $agent, array $input = [], array $metadata = [], ?string $conversationId = null): Execution
    {
        $execution = new Execution(
            id: (string) Str::uuid(),
            agent: $agent,
            status: ExecutionStatus::Running,
            input: $input,
            conversationId: $conversationId,
            metadata: $metadata,
            startedAt: now()->toISOString(),
        );

        $execution = $this->repository->store($execution);

        $this->events?->dispatch(new AgentExecutionStarted(
            executionId: $execution->id,
            agent: $agent,
            message: (string) ($input['message'] ?? ''),
        ));

        return $execution;
    }

    public function addStep(
        Execution $execution,
        string $type,
        array $input = [],
        mixed $output = null,
        ExecutionStatus $status = ExecutionStatus::Completed,
        array $metadata = [],
        ?int $toolId = null,
        ?int $toolVersionId = null,
        ?bool $permissionAllowed = null,
        ?int $durationMs = null,
    ): Execution {
        $step = new ExecutionStep(
            id: (string) Str::uuid(),
            executionId: $execution->id,
            type: $type,
            status: $status,
            input: $input,
            output: $output,
            metadata: $metadata,
            startedAt: now()->toISOString(),
            completedAt: $status === ExecutionStatus::Running ? null : now()->toISOString(),
            toolId: $toolId,
            toolVersionId: $toolVersionId,
            permissionAllowed: $permissionAllowed,
            durationMs: $durationMs,
        );

        $execution->steps[] = $step;

        return $this->repository->update($execution);
    }

    public function complete(Execution $execution, mixed $output = null): Execution
    {
        $execution->status = ExecutionStatus::Completed;
        $execution->output = $output;
        $execution->completedAt = now()->toISOString();
        $execution->error = null;

        $execution = $this->repository->update($execution);

        $this->events?->dispatch(new AgentExecutionCompleted(
            executionId: $execution->id,
            agent: $execution->agent,
            output: $output,
        ));

        return $execution;
    }

    public function fail(Execution $execution, string $error): Execution
    {
        $execution->status = ExecutionStatus::Failed;
        $execution->error = $error;
        $execution->failedAt = now()->toISOString();
        $execution->completedAt = now()->toISOString();

        $execution = $this->repository->update($execution);

        $this->events?->dispatch(new AgentExecutionFailed(
            executionId: $execution->id,
            agent: $execution->agent,
            error: $error,
        ));

        return $execution;
    }

    public function cancel(Execution $execution): Execution
    {
        $execution->status = ExecutionStatus::Cancelled;
        $execution->completedAt = now()->toISOString();

        return $this->repository->update($execution);
    }

    public function waiting(Execution $execution): Execution
    {
        $execution->status = ExecutionStatus::Waiting;

        return $this->repository->update($execution);
    }
}
