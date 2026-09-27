<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Agent\ConfigurableAgent;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;
use Laravel\Ai\AnonymousAgent;

final class AgentRuntimeTest extends TestCase
{
    public function test_runtime_delegates_execution_to_laravel_ai_sdk(): void
    {
        AnonymousAgent::fake([
            'Hello from the Laravel AI SDK.',
        ]);

        $tools = app(ToolRegistry::class);
        $skills = app(SkillRegistry::class);

        $tools->register(new class implements ToolContract {
            public function definition(): ToolDefinition
            {
                return new ToolDefinition(
                    name: 'orders.search',
                    description: 'Search orders.',
                    inputSchema: [
                        'query' => ['type' => 'string', 'required' => true],
                    ],
                );
            }

            public function execute(ToolExecutionContext $context): ToolResult
            {
                return ToolResult::success(['hits' => []]);
            }
        });

        $skills->register(new SkillDefinition(
            name: 'orders',
            description: 'Order operations.',
            tools: ['orders.search'],
        ));

        $definition = new AgentDefinition(
            name: 'Support',
            instructions: 'You help with orders.',
            skills: ['orders'],
            slug: 'support',
            model: 'gpt-4.1-mini',
            provider: 'openai',
        );

        $result = app(AgentRuntime::class)->run(
            $definition,
            new AgentExecutionContext('Where is my order?'),
        );

        $this->assertTrue($result->success);
        $this->assertSame('Hello from the Laravel AI SDK.', $result->text());

        AnonymousAgent::assertPrompted(function ($prompt) {
            return $prompt->contains('Where is my order?');
        });
    }

    public function test_configurable_agent_uses_runtime(): void
    {
        AnonymousAgent::fake(['Configured response']);

        $agent = new ConfigurableAgent(
            new AgentDefinition(name: 'Echo', instructions: 'Echo.'),
            app(AgentRuntime::class),
        );

        $result = $agent->run(new AgentExecutionContext('ping'));

        $this->assertTrue($result->success);
        $this->assertSame('Configured response', $result->text());
    }
}
