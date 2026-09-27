<?php

namespace Agentic\Skill\Routing;

use Agentic\Skill\Routing\Strategies\DynamicKeywordSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\LexicalSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\LlmSkillRoutingStrategy;
use Agentic\Skill\Routing\Strategies\SkillHintRoutingStrategy;
use Agentic\Skill\Routing\Support\SkillLlmSelector;

/**
 * Registers deterministic + optional LLM skill routing strategies on SkillRouter.
 */
final class SkillRoutingConfigurator
{
    public function configure(SkillRouter $router): void
    {
        $router->use(new SkillHintRoutingStrategy());

        $router->use(app(DynamicKeywordSkillRoutingStrategy::class));

        $router->use(app(LexicalSkillRoutingStrategy::class));

        if (config('agentic.skill_routing.llm.enabled', false)) {
            $router->use(new LlmSkillRoutingStrategy(
                selector: app(SkillLlmSelector::class),
                provider: config('agentic.skill_routing.llm.provider'),
                model: config('agentic.skill_routing.llm.model'),
                minConfidence: (float) config('agentic.skill_routing.llm.min_confidence', 0.25),
            ));
        }
    }
}
