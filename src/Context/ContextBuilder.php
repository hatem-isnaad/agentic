<?php

namespace Agentic\Context;

use Agentic\Agent\AgentDefinition;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Memory\MemoryManager;
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
        private ?KnowledgeOrchestrator $knowledge = null,
        private ?MemoryManager $memory = null,
    ) {}

    /**
     * @param  list<string>|null  $skillNames
     * @return array{
     *     instructions: string,
     *     skills: list<array{name: string, description: string, tools: list<string>}>,
     *     knowledge: list<mixed>,
     *     memory: list<mixed>,
     *     tools: list<string>
     * }
     */
    public function build(
        AgentDefinition $agent,
        ?string $retrievalQuery = null,
        ?RuntimeContext $runtime = null,
        ?array $skillNames = null,
    ): array {
        $skillNames ??= $agent->skills;
        $selectedSkills = [];

        foreach ($this->skills->resolveMany($skillNames) as $skill) {
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

        $skillTools = $this->skills->composeTools($skillNames);

        $directTools = array_values(array_filter(
            $agent->tools,
            fn (string $tool) => $this->tools->has($tool),
        ));

        $knowledge = $this->inlineKnowledge($agent->knowledge);

        if ($this->knowledge !== null && is_string($retrievalQuery) && $retrievalQuery !== '') {
            $knowledge = array_merge(
                $knowledge,
                array_map(
                    fn (KnowledgeChunk $chunk) => $chunk->toContextArray(),
                    $this->knowledge->retrieveForAgent($agent, $retrievalQuery, $runtime),
                ),
            );
        }

        $memory = [];

        if ($this->memory !== null && $runtime !== null) {
            $memory = array_map(
                fn ($record) => $record->toContextArray(),
                $this->memory->recallForRuntime($runtime, $agent->identifier()),
            );
        }

        return [
            'instructions' => $agent->instructions,
            'skills' => $selectedSkills,
            'knowledge' => $knowledge,
            'memory' => $memory,
            'tools' => array_values(array_unique(array_merge($directTools, $skillTools))),
        ];
    }

    /**
     * @param  list<mixed>  $entries
     * @return list<mixed>
     */
    private function inlineKnowledge(array $entries): array
    {
        $inline = [];

        foreach ($entries as $entry) {
            if (is_array($entry) && array_key_exists('content', $entry)) {
                $inline[] = $entry;
            }
        }

        return $inline;
    }
}
