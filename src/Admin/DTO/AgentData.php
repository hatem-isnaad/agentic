<?php

namespace Agentic\Admin\DTO;

use Agentic\Agent\AgentDefinition;

final readonly class AgentData
{
    /**
     * @param  list<string>  $skills
     * @param  list<string>  $tools
     * @param  list<mixed>  $knowledge
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $runtime
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?string $instructions = null,
        public ?string $status = null,
        public ?string $provider = null,
        public ?string $model = null,
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public array $skills = [],
        public array $tools = [],
        public array $knowledge = [],
        public array $permissions = [],
        public array $runtime = [],
        public array $config = [],
        public ?string $id = null,
    ) {}

    public static function fromDefinition(AgentDefinition $agent): self
    {
        return new self(
            name: $agent->name,
            slug: $agent->slug ?? $agent->identifier(),
            description: $agent->description,
            instructions: $agent->instructions,
            status: $agent->status,
            provider: $agent->provider,
            model: $agent->model,
            temperature: $agent->temperature,
            maxTokens: $agent->maxTokens,
            skills: $agent->skills,
            tools: $agent->tools,
            knowledge: $agent->knowledge,
            permissions: $agent->permissions,
            runtime: $agent->runtime,
            config: $agent->metadata['config'] ?? [],
            id: $agent->id,
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: $validated['name'],
            slug: $validated['slug'],
            description: $validated['description'] ?? null,
            instructions: $validated['instructions'] ?? null,
            status: $validated['status'] ?? null,
            provider: $validated['provider'] ?? null,
            model: $validated['model'] ?? null,
            temperature: isset($validated['temperature']) ? (float) $validated['temperature'] : null,
            maxTokens: isset($validated['max_tokens']) ? (int) $validated['max_tokens'] : null,
            skills: $validated['skills'] ?? [],
            tools: $validated['tools'] ?? [],
            knowledge: $validated['knowledge'] ?? [],
            permissions: $validated['permissions'] ?? [],
            runtime: $validated['runtime'] ?? [],
            config: $validated['config'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'provider' => $this->provider,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'skills' => $this->skills,
            'tools' => $this->tools,
            'knowledge' => $this->knowledge,
            'permissions' => $this->permissions,
            'runtime' => $this->runtime,
            'config' => $this->config,
        ];
    }

    public function toRepositoryAttributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'provider' => $this->provider,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'skills' => $this->skills,
            'tools' => $this->tools,
            'knowledge' => $this->knowledge,
            'permissions' => $this->permissions,
            'runtime' => $this->runtime,
            'config' => $this->config,
        ], fn ($value) => $value !== null);
    }
}
