<?php

namespace Agentic\Admin\DTO;

use Agentic\Skill\SkillDefinition;

final readonly class SkillData
{
    /**
     * @param  list<string>  $tools
     * @param  list<mixed>  $knowledge
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?string $instructions = null,
        public ?string $status = null,
        public array $tools = [],
        public array $knowledge = [],
        public array $config = [],
        public int|string|null $id = null,
    ) {}

    public static function fromDefinition(SkillDefinition $skill): self
    {
        return new self(
            name: $skill->metadata['display_name'] ?? $skill->name,
            slug: $skill->name,
            description: $skill->description,
            instructions: $skill->metadata['instructions'] ?? null,
            status: $skill->metadata['status'] ?? null,
            tools: $skill->tools,
            knowledge: $skill->knowledge,
            config: $skill->metadata['config'] ?? [],
            id: $skill->metadata['id'] ?? null,
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
            tools: $validated['tools'] ?? [],
            knowledge: $validated['knowledge'] ?? [],
            config: $validated['config'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRepositoryAttributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'tools' => $this->tools,
            'knowledge' => $this->knowledge,
            'config' => $this->config,
        ], fn ($value) => $value !== null);
    }
}
