<?php

namespace Agentic\Skill;

use Agentic\Agent\AgentDefinition;

/**
 * Selects request-relevant skills before the AI SDK receives tools.
 *
 * Routing is deterministic and configuration-driven. Skills may define
 * metadata.keywords as a list of request keywords.
 */
final class SkillRouter
{
    public function __construct(
        private SkillResolver $skills,
    ) {}

    public function select(AgentDefinition $agent, string $message, int $limit = 3): SkillSelection
    {
        $message = mb_strtolower(trim($message));
        $candidates = $this->skills->resolveMany($agent->skills);

        if ($message === '' || $candidates === []) {
            return new SkillSelection($agent->skills);
        }

        $ranked = [];

        foreach ($candidates as $skill) {
            $keywords = $skill->metadata['keywords'] ?? [];

            if (! is_array($keywords)) {
                continue;
            }

            $matches = [];

            foreach ($keywords as $keyword) {
                if (! is_string($keyword) || $keyword === '') {
                    continue;
                }

                if (str_contains($message, mb_strtolower($keyword))) {
                    $matches[] = $keyword;
                }
            }

            if ($matches !== []) {
                $ranked[$skill->name] = $matches;
            }
        }

        if ($ranked === []) {
            return new SkillSelection($agent->skills);
        }

        uasort($ranked, fn (array $a, array $b) => count($b) <=> count($a));

        $selected = array_slice(array_keys($ranked), 0, max(1, $limit));

        return new SkillSelection(
            skills: $selected,
            matches: array_intersect_key($ranked, array_flip($selected)),
        );
    }
}
