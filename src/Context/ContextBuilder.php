<?php

namespace Agentic\Context;

use Agentic\Agent\AgentDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Tool\Registry\ToolRegistry;

final class ContextBuilder
{
    public function __construct(
        private ToolRegistry $tools,
        private SkillRegistry $skills,
    ) {}

    public function build(AgentDefinition $agent): array
    {
        $selectedSkills = [];

        foreach ($agent->skills as $skillName) {
            if (! $this->skills->has($skillName)) {
                continue;
            }

            $skill = $this->skills->get($skillName);

            $selectedSkills[] = [
                'name' => $skill->name,
                'description' => $skill->description,
                'tools' => array_values(array_filter(
                    $skill->tools,
                    fn (string $tool) => $this->tools->has($tool),
                )),
            ];
        }

        return [
            'instructions' => $agent->instructions,
            'skills' => $selectedSkills,
            'knowledge' => $agent->knowledge,
        ];
    }
}
