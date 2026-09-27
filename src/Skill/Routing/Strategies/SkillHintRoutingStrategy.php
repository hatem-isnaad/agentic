<?php

namespace Agentic\Skill\Routing\Strategies;

use Agentic\Skill\Routing\Contracts\SkillRoutingStrategy;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\SkillRoutingResult;

/**
 * Uses explicit skill hints from routing context (API metadata or skillHint field).
 */
final class SkillHintRoutingStrategy implements SkillRoutingStrategy
{
    public function name(): string
    {
        return 'skill_hint';
    }

    public function route(SkillRoutingContext $context): ?SkillRoutingResult
    {
        $hints = [];

        if ($context->skillHint !== null && $context->skillHint !== '') {
            $hints[] = $context->skillHint;
        }

        $fromMeta = $context->get('skills');

        if (is_string($fromMeta) && $fromMeta !== '') {
            $hints[] = $fromMeta;
        } elseif (is_array($fromMeta)) {
            foreach ($fromMeta as $item) {
                if (is_string($item) && $item !== '') {
                    $hints[] = $item;
                }
            }
        }

        if ($hints === []) {
            return null;
        }

        $selected = array_values(array_unique(array_intersect(
            $context->candidateSkills,
            $hints,
        )));

        if ($selected === []) {
            return null;
        }

        return new SkillRoutingResult(
            skills: $selected,
            strategy: $this->name(),
            confidence: 1.0,
            metadata: ['hints' => $hints],
        );
    }
}
