<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\ContextBuilder;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class ContextBuilderTest extends TestCase
{
    public function test_context_contains_only_registered_tools_from_selected_skills(): void
    {
        $tools = new ToolRegistry();
        $skills = new SkillRegistry();

        $tools->register(new class implements ToolContract {
            public function definition(): ToolDefinition
            {
                return new ToolDefinition('orders.search', 'Search orders.');
            }

            public function execute(ToolExecutionContext $context): ToolResult
            {
                return ToolResult::success();
            }
        });

        $skills->register(new SkillDefinition(
            name: 'orders',
            description: 'Order operations.',
            tools: ['orders.search', 'missing.tool'],
        ));

        $context = (new ContextBuilder($tools, new SkillResolver($skills, $tools)))->build(
            new AgentDefinition(
                name: 'support',
                instructions: 'Help users with orders.',
                skills: ['orders'],
            )
        );

        $this->assertSame(['orders.search'], $context['skills'][0]['tools']);
    }

    public function test_inactive_skills_are_skipped(): void
    {
        $tools = new ToolRegistry();
        $skills = new SkillRegistry();
        $skills->register(new SkillDefinition(
            name: 'archived-skill',
            description: 'Old',
            tools: [],
            metadata: ['status' => 'archived'],
        ));

        $context = (new ContextBuilder($tools, new SkillResolver($skills, $tools)))->build(
            new AgentDefinition(name: 'a', skills: ['archived-skill']),
        );

        $this->assertSame([], $context['skills']);
    }
}
