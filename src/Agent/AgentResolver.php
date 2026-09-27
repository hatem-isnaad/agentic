<?php

namespace Agentic\Agent;

use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Skill\SkillResolver;
use Agentic\Tool\ToolFactory;

final class AgentResolver
{
    public function __construct(
        private AgentRepository $agents,
        private SkillResolver $skills,
        private ToolFactory $tools,
    ) {}

    public function resolve(string $slug): AgentDefinition
    {
        $agent = $this->agents->findBySlug($slug);

        if ($agent === null) {
            throw new AgentNotFoundException($slug);
        }

        foreach ($agent->skills as $skillSlug) {
            try {
                $this->skills->resolve($skillSlug);
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($agent->tools as $toolSlug) {
            $this->tools->ensureRegistered($toolSlug);
        }

        return $agent;
    }
}
