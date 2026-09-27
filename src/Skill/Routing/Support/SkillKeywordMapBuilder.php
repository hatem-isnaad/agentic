<?php

namespace Agentic\Skill\Routing\Support;

use Agentic\Skill\SkillResolver;

/**
 * Merges config keyword_map with per-skill metadata for runtime routing.
 */
final class SkillKeywordMapBuilder
{
    public function __construct(
        private SkillResolver $skills,
    ) {}

    /**
     * @param  list<string>  $candidateSkillNames
     * @return array<string, list<string>>
     */
    public function build(array $candidateSkillNames): array
    {
        $map = config('agentic.skill_routing.keyword_map', []);
        $map = is_array($map) ? $map : [];

        if (! config('agentic.skill_routing.use_skill_metadata', true)) {
            return $this->filterCandidates($map, $candidateSkillNames);
        }

        foreach ($this->skills->resolveMany($candidateSkillNames) as $skill) {
            $fromMeta = $skill->metadata['routing_keywords']
                ?? $skill->metadata['keywords']
                ?? [];

            if (! is_array($fromMeta)) {
                continue;
            }

            $keywords = array_values(array_filter(
                $fromMeta,
                fn ($keyword) => is_string($keyword) && $keyword !== '',
            ));

            if ($keywords === []) {
                continue;
            }

            $existing = $map[$skill->name] ?? [];
            $map[$skill->name] = array_values(array_unique([...$existing, ...$keywords]));
        }

        return $this->filterCandidates($map, $candidateSkillNames);
    }

    /**
     * @param  array<string, list<string>>  $map
     * @param  list<string>  $candidateSkillNames
     * @return array<string, list<string>>
     */
    private function filterCandidates(array $map, array $candidateSkillNames): array
    {
        $filtered = [];

        foreach ($candidateSkillNames as $name) {
            if (isset($map[$name]) && $map[$name] !== []) {
                $filtered[$name] = $map[$name];
            }
        }

        return $filtered;
    }
}
