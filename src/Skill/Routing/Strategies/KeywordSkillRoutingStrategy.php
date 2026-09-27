<?php

namespace Agentic\Skill\Routing\Strategies;

use Agentic\Skill\Routing\Contracts\SkillRoutingStrategy;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\SkillRoutingResult;

/**
 * Deterministic skill selection by keyword hits in the user message.
 *
 * @example ['orders' => ['order', 'refund'], 'billing' => ['invoice', 'payment']]
 */
final class KeywordSkillRoutingStrategy implements SkillRoutingStrategy
{
    /**
     * @param  array<string, list<string>>  $map  skillName => keywords
     */
    public function __construct(
        private array $map = [],
    ) {}

    public function name(): string
    {
        return 'keyword';
    }

    public function route(SkillRoutingContext $context): ?SkillRoutingResult
    {
        $message = strtolower($context->message);

        if ($message === '' || $this->map === []) {
            return null;
        }

        $selected = [];
        $matchedKeywords = [];

        foreach ($context->candidateSkills as $skillName) {
            $keywords = $this->map[$skillName] ?? [];

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($message, strtolower($keyword))) {
                    $selected[] = $skillName;
                    $matchedKeywords[$skillName][] = $keyword;
                    break;
                }
            }
        }

        $selected = array_values(array_unique($selected));

        if ($selected === []) {
            return null;
        }

        $confidence = min(1.0, count($selected) / max(1, count($context->candidateSkills)));

        return new SkillRoutingResult(
            skills: $selected,
            strategy: $this->name(),
            confidence: $confidence,
            metadata: ['matched' => $matchedKeywords],
        );
    }
}
