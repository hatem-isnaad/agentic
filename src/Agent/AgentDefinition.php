<?php

namespace Agentic\Agent;

/**
 * Immutable runtime configuration for an Agent.
 *
 * Agents do not execute AI themselves — AgentRuntime does.
 */
final readonly class AgentDefinition
{
    /**
     * @param  list<string>  $skills
     * @param  list<string>  $tools
     * @param  list<mixed>  $knowledge
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $runtime
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $name,
        public string $instructions = '',
        public array $skills = [],
        public array $knowledge = [],
        public array $metadata = [],
        public ?string $id = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $model = null,
        public ?string $provider = null,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public array $tools = [],
        public array $permissions = [],
        public array $runtime = [],
        public ?string $status = null,
    ) {}

    public function identifier(): string
    {
        return $this->slug ?? $this->name;
    }
}
