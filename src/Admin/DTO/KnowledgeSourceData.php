<?php

namespace Agentic\Admin\DTO;

use Agentic\Knowledge\KnowledgeSourceDefinition;

final readonly class KnowledgeSourceData
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public string $driver = 'array',
        public ?string $status = null,
        public array $config = [],
    ) {}

    public static function fromDefinition(KnowledgeSourceDefinition $source): self
    {
        return new self(
            name: $source->name,
            slug: $source->slug,
            description: $source->metadata['description'] ?? null,
            driver: $source->driver,
            status: $source->status,
            config: $source->configuration,
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
            driver: $validated['driver'] ?? 'array',
            status: $validated['status'] ?? null,
            config: $validated['config'] ?? [],
        );
    }

    public function toDefinition(): KnowledgeSourceDefinition
    {
        return new KnowledgeSourceDefinition(
            slug: $this->slug,
            name: $this->name,
            driver: $this->driver,
            configuration: $this->config,
            status: $this->status ?? 'draft',
            metadata: [
                'description' => $this->description,
            ],
        );
    }
}
