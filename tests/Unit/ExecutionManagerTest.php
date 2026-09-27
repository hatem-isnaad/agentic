<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Events\AgentExecutionCompleted;
use Agentic\Events\AgentExecutionStarted;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\ExecutionManager;
use Agentic\Execution\ExecutionStatus;
use Agentic\Persistence\InMemory\InMemoryExecutionRepository;
use Agentic\Runtime\AgentRuntime;
use Agentic\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\AnonymousAgent;

final class ExecutionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ExecutionRepository::class, new InMemoryExecutionRepository());
    }

    public function test_execution_lifecycle_is_tracked(): void
    {
        Event::fake([AgentExecutionStarted::class, AgentExecutionCompleted::class]);

        $manager = app(ExecutionManager::class);
        $execution = $manager->start('support', ['message' => 'hi']);

        $this->assertSame(ExecutionStatus::Running, $execution->status);

        $execution = $manager->addStep($execution, 'llm_request', ['message' => 'hi']);
        $execution = $manager->complete($execution, ['text' => 'hello']);

        $stored = app(ExecutionRepository::class)->find($execution->id);

        $this->assertNotNull($stored);
        $this->assertSame(ExecutionStatus::Completed, $stored->status);
        $this->assertCount(1, $stored->steps);
        Event::assertDispatched(AgentExecutionStarted::class);
        Event::assertDispatched(AgentExecutionCompleted::class);
    }

    public function test_agent_runtime_records_execution(): void
    {
        AnonymousAgent::fake(['Tracked response']);

        $result = app(AgentRuntime::class)->run(
            new AgentDefinition(name: 'Support', slug: 'support', instructions: 'Help.'),
            new AgentExecutionContext('Hello'),
        );

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->output['execution_id'] ?? null);

        $execution = app(ExecutionRepository::class)->find($result->output['execution_id']);

        $this->assertNotNull($execution);
        $this->assertSame(ExecutionStatus::Completed, $execution->status);
        $this->assertGreaterThanOrEqual(2, count($execution->steps));
    }
}
