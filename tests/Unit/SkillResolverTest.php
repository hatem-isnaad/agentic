<?php

namespace Agentic\Tests\Unit;

use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class SkillResolverTest extends TestCase
{
    public function test_compose_tools_merges_unique_registered_tools(): void
    {
        $tools = app(ToolRegistry::class);
        $skills = app(SkillRegistry::class);

        foreach (['orders.search', 'orders.get', 'orders.cancel'] as $name) {
            $tools->register(new class($name) implements ToolContract {
                public function __construct(private string $name) {}

                public function definition(): ToolDefinition
                {
                    return new ToolDefinition($this->name, $this->name);
                }

                public function execute(ToolExecutionContext $context): ToolResult
                {
                    return ToolResult::success();
                }
            });
        }

        $skills->register(new SkillDefinition('orders', tools: ['orders.search', 'orders.get']));
        $skills->register(new SkillDefinition('orders-admin', tools: ['orders.get', 'orders.cancel']));

        $composed = app(SkillResolver::class)->composeTools(['orders', 'orders-admin']);

        $this->assertSame(['orders.search', 'orders.get', 'orders.cancel'], $composed);
    }
}
