<?php

namespace Agentic\Admin\DTO;

use Agentic\Tool\ToolDefinition;

final readonly class ToolData
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $definition
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $driver,
        public ?string $description = null,
        public ?string $status = null,
        public array $config = [],
        public array $definition = [],
        public ?bool $publish = null,
    ) {}

    public static function fromDefinition(ToolDefinition $tool): self
    {
        return new self(
            name: $tool->description !== '' ? $tool->description : $tool->name,
            slug: $tool->name,
            driver: (string) $tool->driver,
            description: $tool->description,
            status: $tool->status,
            config: $tool->configuration,
            definition: [],
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
            driver: $validated['driver'],
            description: $validated['description'] ?? null,
            status: $validated['status'] ?? null,
            config: $validated['config'] ?? [],
            definition: $validated['definition'] ?? [],
            publish: $validated['publish'] ?? null,
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
            'driver' => $this->driver,
            'description' => $this->description,
            'status' => $this->status,
            'config' => $this->config,
            'definition' => $this->definition,
            'publish' => $this->publish,
        ], fn ($value) => $value !== null);
    }
}
