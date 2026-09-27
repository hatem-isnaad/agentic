<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Skill\Routing\SkillRouter;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\Strategies\DynamicKeywordSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\KeywordSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\LexicalSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\SkillHintRoutingStrategy;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Tests\TestCase;

final class SkillRouterTest extends TestCase
{
    public function test_keyword_strategy_selects_subset_of_skills(): void
    {
        $router = (new SkillRouter(fallback: 'none'))
            ->use(new KeywordSkillRoutingStrategy([
                'orders' => ['refund', 'order'],
                'billing' => ['invoice'],
            ]));

        $result = $router->route(new SkillRoutingContext(
            agent: new AgentDefinition(name: 'Support', skills: ['orders', 'billing', 'general']),
            message: 'I need a refund on my order',
            candidateSkills: ['orders', 'billing', 'general'],
        ));

        $this->assertSame('keyword', $result->strategy);
        $this->assertSame(['orders'], $result->skills);
    }

    public function test_skill_hint_runs_before_keyword(): void
    {
        $router = (new SkillRouter(fallback: 'none'))
            ->use(new SkillHintRoutingStrategy())
            ->use(new KeywordSkillRoutingStrategy([
                'orders' => ['refund'],
            ]));

        $result = $router->route(new SkillRoutingContext(
            agent: new AgentDefinition(name: 'Support', skills: ['orders', 'billing']),
            message: 'refund please',
            candidateSkills: ['orders', 'billing'],
            attributes: ['skills' => ['billing']],
        ));

        $this->assertSame('skill_hint', $result->strategy);
        $this->assertSame(['billing'], $result->skills);
    }

    public function test_dynamic_keyword_map_uses_skill_metadata(): void
    {
        app(SkillRegistry::class)->register(new SkillDefinition(
            name: 'billing',
            description: 'Billing',
            tools: [],
            metadata: ['routing_keywords' => ['invoice', 'payment']],
        ));

        $router = (new SkillRouter(fallback: 'none'))
            ->use(app(DynamicKeywordSkillRoutingStrategy::class));

        $result = $router->route(new SkillRoutingContext(
            agent: new AgentDefinition(name: 'Support', skills: ['billing']),
            message: 'payment failed',
            candidateSkills: ['billing'],
        ));

        $this->assertSame('keyword', $result->strategy);
        $this->assertSame(['billing'], $result->skills);
    }

    public function test_lexical_strategy_matches_skill_description(): void
    {
        app(SkillRegistry::class)->register(new SkillDefinition(
            name: 'returns',
            description: 'Handle product returns and refunds within policy windows',
            tools: [],
        ));

        app(SkillRegistry::class)->register(new SkillDefinition(
            name: 'shipping',
            description: 'Track packages and delivery status',
            tools: [],
        ));

        $router = (new SkillRouter(fallback: 'none'))
            ->use(app(LexicalSkillRoutingStrategy::class));

        $result = $router->route(new SkillRoutingContext(
            agent: new AgentDefinition(name: 'Support', skills: ['returns', 'shipping']),
            message: 'I want to return this item and get a refund',
            candidateSkills: ['returns', 'shipping'],
        ));

        $this->assertSame('lexical', $result->strategy);
        $this->assertSame(['returns'], $result->skills);
    }

    public function test_fallback_core_uses_agent_runtime_core_skills(): void
    {
        $router = new SkillRouter(fallback: 'core');

        $result = $router->route(new SkillRoutingContext(
            agent: new AgentDefinition(
                name: 'Support',
                skills: ['orders', 'billing', 'general'],
                runtime: ['core_skills' => ['general']],
            ),
            message: 'hello',
            candidateSkills: ['orders', 'billing', 'general'],
        ));

        $this->assertSame('fallback:core', $result->strategy);
        $this->assertSame(['general'], $result->skills);
    }
}
