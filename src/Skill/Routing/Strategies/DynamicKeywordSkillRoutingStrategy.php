<?php

namespace Agentic\Skill\Routing\Strategies;

use Agentic\Skill\Routing\Contracts\SkillRoutingStrategy;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\SkillRoutingResult;
use Agentic\Skill\Routing\Support\SkillKeywordMapBuilder;

/**
 * Keyword routing using merged config + skill metadata (per agent candidates).
 */
final class DynamicKeywordSkillRoutingStrategy implements SkillRoutingStrategy
{
    public function __construct(
        private SkillKeywordMapBuilder $keywordMap,
    ) {}

    public function name(): string
    {
        return 'keyword';
    }

    public function route(SkillRoutingContext $context): ?SkillRoutingResult
    {
        $map = $this->keywordMap->build($context->candidateSkills);

        if ($map === []) {
            return null;
        }

        return (new KeywordSkillRoutingStrategy($map))->route($context);
    }
}
