<?php

namespace Agentic\Skill;

use Agentic\Agent\AgentDefinition;

/**
 * Selects request-relevant skills before the AI SDK receives tools.
 *
 * Deterministic keyword routing is the fast path. Optional Laravel AI SDK
 * classification is used only when deterministic routing finds no match.
 */
final class SkillRouter
{
    public function __construct(
        private SkillResolver $skills,
        private ?LlmSkillRouter $llm = null,
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

        if ($ranked !== []) {
            uasort($ranked, fn (array $a, array $b) => count($b) <=> count($a));

            $selected = array_slice(array_keys($ranked), 0, max(1, $limit));

            return new SkillSelection(
                skills: $selected,
                matches: array_intersect_key($ranked, array_flip($selected)),
            );
        }

        if ((bool) config('agentic.skill_routing.ai.enabled', false) && $this->llm !== null) {
            $selection = $this->llm->select($agent->skills, $message);

            if ($selection !== null) {
                return $selection;
            }
        }

        return new SkillSelection($agent->skills);
    }
}
