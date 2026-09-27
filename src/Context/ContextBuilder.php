<?php

namespace Agentic\Context;

use Agentic\Agent\AgentDefinition;
use Agentic\Agent\AgentPersona;
use Agentic\Agent\AgentPersonaComposer;
use Agentic\Reply\ChannelPresentationComposer;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Mcp\McpAgentKnowledgeEnricher;
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
        private ?McpAgentKnowledgeEnricher $mcpKnowledge = null,
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
        $policy = $this->contextPolicy($runtime);
        $skillNames ??= $agent->skills;
        $selectedSkills = [];
        $compactSkills = (bool) ($policy['compact_skill_descriptions'] ?? false);

        foreach ($this->skills->resolveMany($skillNames) as $skill) {
            $tools = array_values(array_filter(
                $skill->tools,
                fn (string $tool) => $this->tools->has($tool),
            ));

            $description = $skill->description;
            if ($compactSkills && mb_strlen($description) > 160) {
                $description = mb_substr($description, 0, 157).'…';
            }

            $selectedSkills[] = [
                'name' => $skill->name,
                'description' => $description,
                'tools' => $tools,
            ];
        }

        $skillTools = $this->skills->composeTools($skillNames);

        $directTools = array_values(array_filter(
            $agent->tools,
            fn (string $tool) => $this->tools->has($tool),
        ));

        $knowledge = $this->inlineKnowledge($agent->knowledge);

        if ($this->mcpKnowledge !== null) {
            $knowledge = array_merge($knowledge, $this->mcpKnowledge->enrich($agent));
        }

        $knowledgeLimit = (int) ($policy['knowledge_chunk_limit'] ?? 5);

        if ($this->knowledge !== null && is_string($retrievalQuery) && $retrievalQuery !== '' && $knowledgeLimit > 0) {
            $knowledge = array_merge(
                $knowledge,
                array_map(
                    fn (KnowledgeChunk $chunk) => $chunk->toContextArray(),
                    $this->knowledge->retrieveForAgent($agent, $retrievalQuery, $runtime, $knowledgeLimit),
                ),
            );
        }

        $memory = [];

        if ($this->memory !== null && $runtime !== null) {
            $memory = array_map(
                fn ($record) => $record->toContextArray(),
                $this->memory->recallForRuntime($runtime, $agent->identifier()),
            );

            $memoryLimit = (int) ($policy['memory_entry_limit'] ?? 0);
            if ($memoryLimit > 0 && count($memory) > $memoryLimit) {
                $memory = array_slice($memory, 0, $memoryLimit);
            }
        }

        return [
            'instructions' => (new ChannelPresentationComposer())->compose(
                (new AgentPersonaComposer())->compose(
                    $agent->instructions,
                    AgentPersona::fromAgent($agent),
                ),
                $runtime,
            ),
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

    /**
     * @return array<string, mixed>
     */
    private function contextPolicy(?RuntimeContext $runtime): array
    {
        if ($runtime === null) {
            return [];
        }

        $policy = $runtime->get('context_policy');

        return is_array($policy) ? $policy : [];
    }
}
