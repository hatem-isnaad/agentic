<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Skill\SkillRouter;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tests\TestCase;

final class SkillRouterTest extends TestCase
{
    public function test_selects_skills_by_configured_keywords(): void
    {
        $registry = new SkillRegistry();
        $registry->register(new SkillDefinition(
            name: 'orders',
            description: 'Orders',
            metadata: ['keywords' => ['order', 'shipment']],
        ));
        $registry->register(new SkillDefinition(
            name: 'billing',
            description: 'Billing',
            metadata: ['keywords' => ['invoice', 'payment']],
        ));

        $router = new SkillRouter(
            new SkillResolver($registry, new ToolRegistry()),
        );

        $result = $router->select(
            new AgentDefinition('support', skills: ['orders', 'billing']),
            'Where is my order shipment?',
        );

        $this->assertSame(['orders'], $result->skills);
        $this->assertSame(['order', 'shipment'], $result->matches['orders']);
    }

    public function test_falls_back_to_all_agent_skills_when_no_keyword_matches(): void
    {
        $registry = new SkillRegistry();
        $registry->register(new SkillDefinition(
            name: 'orders',
            description: 'Orders',
        ));

        $router = new SkillRouter(
            new SkillResolver($registry, new ToolRegistry()),
        );

        $result = $router->select(
            new AgentDefinition('support', skills: ['orders']),
            'Hello there',
        );

        $this->assertSame(['orders'], $result->skills);
    }
}
