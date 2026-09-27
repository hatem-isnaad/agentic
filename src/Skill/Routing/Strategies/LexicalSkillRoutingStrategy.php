<?php

namespace Agentic\Skill\Routing\Strategies;

use Agentic\Skill\Routing\Contracts\SkillRoutingStrategy;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\SkillRoutingResult;
use Agentic\Skill\Routing\Support\LexicalSkillScorer;
use Agentic\Skill\SkillResolver;

/**
 * Scores candidate skills by lexical overlap with the user message (before optional LLM routing).
 */
final class LexicalSkillRoutingStrategy implements SkillRoutingStrategy
{
    public function __construct(
        private SkillResolver $skills,
        private LexicalSkillScorer $scorer,
    ) {}

    public function name(): string
    {
        return 'lexical';
    }

    public function route(SkillRoutingContext $context): ?SkillRoutingResult
    {
        if (! config('agentic.skill_routing.lexical.enabled', true)) {
            return null;
        }

        $definitions = $this->skills->resolveMany($context->candidateSkills);

        if ($definitions === []) {
            return null;
        }

        $scores = $this->scorer->scoreMessage($context->message, $definitions);

        if ($scores === []) {
            return null;
        }

        $minScore = (float) config('agentic.skill_routing.lexical.min_score', 0.12);
        $maxSkills = (int) config('agentic.skill_routing.lexical.max_skills', 3);

        arsort($scores);

        $selected = [];

        foreach ($scores as $skill => $score) {
            if ($score < $minScore) {
                continue;
            }

            $selected[] = $skill;

            if (count($selected) >= $maxSkills) {
                break;
            }
        }

        if ($selected === []) {
            return null;
        }

        $confidence = min(1.0, max($scores));

        return new SkillRoutingResult(
            skills: $selected,
            strategy: $this->name(),
            confidence: $confidence,
            metadata: [
                'scores' => array_intersect_key($scores, array_flip($selected)),
                'min_score' => $minScore,
            ],
        );
    }
}
