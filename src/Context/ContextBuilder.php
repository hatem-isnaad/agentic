<?php

namespace Agentic\Context;

use Agentic\Agent\AgentDefinition;
use Agentic\Skill\SkillResolver;
use Agentic\Tool\Registry\ToolRegistry;

/**
 * Builds the structured prompt/context payload for an AgentDefinition.
 */
final class ContextBuilder
{
    public function __construct(
        private ToolRegistry $tools,
        private SkillResolver $skills,
    ) {}

    /**
     * @return array{
     *     instructions: string,
     *     skills: list<array{name: string, description: string, tools: list<string>}>,
     *     knowledge: list<mixed>,
     *     tools: list<string>
     * }
     */
    public function build(AgentDefinition $agent): array
    {
        $selectedSkills = [];

        foreach ($this->skills->resolveMany($agent->skills) as $skill) {
            $tools = array_values(array_filter(
                $skill->tools,
                fn (string $tool) => $this->tools->has($tool),
            ));

            $selectedSkills[] = [
                'name' => $skill->name,
                'description' => $skill->description,
                'tools' => $tools,
            ];
        }

        $skillTools = $this->skills->composeTools($agent->skills);

        $directTools = array_values(array_filter(
            $agent->tools,
            fn (string $tool) => $this->tools->has($tool),
        ));

        return [
            'instructions' => $agent->instructions,
            'skills' => $selectedSkills,
            'knowledge' => $agent->knowledge,
            'tools' => array_values(array_unique(array_merge($directTools, $skillTools))),
        ];
    }
}
