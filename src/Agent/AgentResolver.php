<?php

namespace Agentic\Agent;

use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Skill\SkillRegistry;

/**
 * Resolves persisted Agents into runtime definitions and hydrates skill registry entries.
 */
final class AgentResolver
{
    public function __construct(
        private AgentRepository $agents,
        private SkillRepository $skills,
        private SkillRegistry $skillRegistry,
    ) {}

    public function resolve(string $slug): AgentDefinition
    {
        $agent = $this->agents->findBySlug($slug);

        if ($agent === null) {
            throw new AgentNotFoundException($slug);
        }

        foreach ($agent->skills as $skillSlug) {
            $skill = $this->skills->findBySlug($skillSlug);

            if ($skill !== null && ! $this->skillRegistry->has($skill->name)) {
                $this->skillRegistry->register($skill);
            }
        }

        return $agent;
    }
}
