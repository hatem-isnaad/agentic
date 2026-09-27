<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\ExecutionManager;
use Agentic\Execution\ExecutionStatus;
use Agentic\Persistence\InMemory\InMemoryExecutionRepository;
use Agentic\Runtime\AgentRuntime;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolResult;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\AnonymousAgent;

final class ToolExecutionVersionBindingTest extends TestCase
{
    public function test_tool_definition_exposes_version_and_id(): void
    {
        $definition = new ToolDefinition(
            name: 'orders.get',
            description: 'Get order',
            version: 7,
            id: 42,
        );

        $this->assertSame(7, $definition->version);
        $this->assertSame(42, $definition->id);
    }

    public function test_runtime_context_carries_execution_id(): void
    {
        $context = new AgentExecutionContext('Hello', executionId: 'execution-123');

        $this->assertSame('execution-123', $context->executionId);
    }
}
