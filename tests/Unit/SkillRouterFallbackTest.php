<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillRouter;
use Agentic\Tests\TestCase;

final class SkillRouterFallbackTest extends TestCase
{
    public function test_fallback_limit_caps_skills_when_no_keyword_match(): void
    {
        $skills = app(SkillRegistry::class);

        foreach (['a', 'b', 'c', 'd'] as $name) {
            $skills->register(new SkillDefinition(
                name: $name,
                description: 'Skill '.$name,
                tools: [],
                metadata: ['keywords' => ['zzzz']],
            ));
        }

        $agent = new AgentDefinition(
            name: 'Test',
            skills: ['a', 'b', 'c', 'd'],
            slug: 'test',
        );

        $selection = app(SkillRouter::class)->select($agent, 'hello', 3, 2);

        $this->assertSame(['a', 'b'], $selection->skills);
    }
}
