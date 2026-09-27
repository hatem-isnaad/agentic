<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Skill\Routing\SkillRouter;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\Strategies\LlmSkillRoutingStrategy;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Tests\TestCase;
use Laravel\Ai\AnonymousAgent;

final class SkillLlmRoutingTest extends TestCase
{
    public function test_llm_skill_strategy_selects_from_catalog_only(): void
    {
        AnonymousAgent::fake(['["billing"]']);

        app(SkillRegistry::class)->register(new SkillDefinition(
            name: 'orders',
            description: 'Orders',
            tools: ['orders.search'],
        ));
        app(SkillRegistry::class)->register(new SkillDefinition(
            name: 'billing',
            description: 'Billing',
            tools: ['billing.list'],
        ));

        $router = (new SkillRouter(fallback: 'none'))
            ->use(app(LlmSkillRoutingStrategy::class));

        $result = $router->route(new SkillRoutingContext(
            agent: new AgentDefinition(name: 'Support', skills: ['orders', 'billing']),
            message: 'Where is my invoice?',
            candidateSkills: ['orders', 'billing'],
        ));

        $this->assertNotNull($result);
        $this->assertSame('llm', $result->strategy);
        $this->assertSame(['billing'], $result->skills);
    }
}
