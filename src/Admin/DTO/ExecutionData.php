<?php

namespace Agentic\Admin\DTO;

use Agentic\Execution\Execution;

final readonly class ExecutionData
{
    /**
     * @param  array<string, mixed>  $input
     * @param  mixed  $output
     * @param  array<string, mixed>  $metadata
     * @param  list<array<string, mixed>>  $steps
     */
    public function __construct(
        public string $id,
        public string $agent,
        public string $status,
        public array $input,
        public mixed $output,
        public ?string $error,
        public ?string $conversationId,
        public array $metadata,
        public array $steps,
        public ?string $startedAt,
        public ?string $completedAt,
        public ?string $failedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'agent' => $this->agent,
            'status' => $this->status,
            'input' => $this->input,
            'output' => $this->output,
            'error' => $this->error,
            'conversation_id' => $this->conversationId,
            'metadata' => $this->metadata,
            'steps' => $this->steps,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'failed_at' => $this->failedAt,
        ];
    }

    public static function fromExecution(Execution $execution): self
    {
        return new self(
            id: $execution->id,
            agent: $execution->agent,
            status: $execution->status->value,
            input: $execution->input,
            output: $execution->output,
            error: $execution->error,
            conversationId: $execution->conversationId,
            metadata: $execution->metadata,
            steps: array_map(fn ($step) => [
                'id' => $step->id,
                'type' => $step->type,
                'status' => $step->status->value,
                'input' => $step->input,
                'output' => $step->output,
                'started_at' => $step->startedAt,
                'completed_at' => $step->completedAt,
            ], $execution->steps),
            startedAt: $execution->startedAt,
            completedAt: $execution->completedAt,
            failedAt: $execution->failedAt,
        );
    }
}
