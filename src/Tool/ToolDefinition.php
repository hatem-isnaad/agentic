<?php

namespace Agentic\Tool;

final readonly class ToolDefinition
{
    /**
     * @param  array<string, mixed>  $inputSchema
     * @param  array<string, mixed>  $outputSchema
     * @param  array<string, mixed>  $configuration
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $runtime
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema = [],
        public array $outputSchema = [],
        public ?string $driver = null,
        public array $configuration = [],
        public array $permissions = [],
        public ?string $status = null,
        public array $runtime = [],
        public ?int $version = null,
        public ?string $connection = null,
    ) {}
}
