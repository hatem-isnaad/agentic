<?php

namespace Agentic\Knowledge;

/**
 * Configuration for a knowledge source (DB or code-defined).
 */
final readonly class KnowledgeSourceDefinition
{
    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $driver = 'array',
        public array $configuration = [],
        public ?string $status = null,
        public array $metadata = [],
    ) {}
}
