<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Persistence\InMemory\InMemoryExecutionRepository;
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

final class AgentRuntimeSkillRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ExecutionRepository::class, new InMemoryExecutionRepository());
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.skill_routing.enabled', true);
        $app['config']->set('agentic.skill_routing.fallback', 'none');
        $app['config']->set('agentic.skill_routing.keyword_map', [
            'orders' => ['order'],
            'billing' => ['invoice'],
        ]);
    }

    public function test_runtime_exposes_only_routed_skill_tools_to_llm(): void
    {
        AnonymousAgent::fake(['Routed response']);

        $tools = app(ToolRegistry::class);
        $skills = app(SkillRegistry::class);

        foreach (['orders.search', 'billing.list'] as $toolName) {
            $tools->register(new class($toolName) implements ToolContract {
                public function __construct(private string $name) {}

                public function definition(): ToolDefinition
                {
                    return new ToolDefinition(
                        name: $this->name,
                        description: $this->name,
                        inputSchema: [],
                    );
                }

                public function execute(ToolExecutionContext $context): ToolResult
                {
                    return ToolResult::success([]);
                }
            });
        }

        $skills->register(new SkillDefinition('orders', 'Orders', ['orders.search']));
        $skills->register(new SkillDefinition('billing', 'Billing', ['billing.list']));

        $agent = new AgentDefinition(
            name: 'Support',
            instructions: 'Help',
            skills: ['orders', 'billing'],
            slug: 'support',
            provider: 'openai',
            model: 'gpt-4.1-mini',
        );

        $result = app(AgentRuntime::class)->run(
            $agent,
            new AgentExecutionContext('Question about my invoice'),
        );

        $this->assertTrue($result->success, $result->error ?? 'runtime failed');

        $execution = app(ExecutionRepository::class)->find($result->output['execution_id']);
        $this->assertNotNull($execution);

        $routingStep = collect($execution->steps)->firstWhere('type', 'skill_routing');
        $this->assertNotNull($routingStep);
        $this->assertSame(['billing'], $routingStep->input['skills'] ?? null);

        $llmStep = collect($execution->steps)->firstWhere('type', 'llm_request');
        $this->assertSame(['billing.list'], $llmStep->input['tools'] ?? null);
        $this->assertSame(['billing'], $llmStep->input['skills'] ?? null);
    }
}
