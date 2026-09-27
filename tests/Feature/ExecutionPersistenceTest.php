<?php

namespace Agentic\Tests\Feature;

use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Execution\Execution;
use Agentic\Execution\ExecutionStatus;
use Agentic\Execution\ExecutionStep;
use Agentic\Persistence\Eloquent\EloquentExecutionRepository;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

final class ExecutionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eloquent_execution_repository_persists_steps(): void
    {
        $this->app->bind(ExecutionRepository::class, EloquentExecutionRepository::class);

        $id = (string) Str::uuid();
        $repo = app(ExecutionRepository::class);

        $repo->store(new Execution(
            id: $id,
            agent: 'support',
            status: ExecutionStatus::Running,
            input: ['message' => 'hi'],
            startedAt: now()->toISOString(),
            steps: [
                new ExecutionStep(
                    id: (string) Str::uuid(),
                    executionId: $id,
                    type: 'agent_start',
                    status: ExecutionStatus::Completed,
                    startedAt: now()->toISOString(),
                    completedAt: now()->toISOString(),
                ),
            ],
        ));

        $found = $repo->find($id);

        $this->assertNotNull($found);
        $this->assertSame('support', $found->agent);
        $this->assertCount(1, $found->steps);
        $this->assertSame('agent_start', $found->steps[0]->type);
    }
}
