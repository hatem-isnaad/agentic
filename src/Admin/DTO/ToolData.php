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
        public ?int $id = null,
    ) {}

    public static function fromDefinition(ToolDefinition $tool): self
    {
        $definition = array_filter([
            'input_schema' => $tool->inputSchema !== [] ? $tool->inputSchema : null,
            'output_schema' => $tool->outputSchema !== [] ? $tool->outputSchema : null,
            'method' => $tool->configuration['method'] ?? null,
            'url' => $tool->configuration['url'] ?? null,
            'auth' => $tool->configuration['auth'] ?? null,
            'timeout' => $tool->configuration['timeout'] ?? null,
            'retry' => $tool->configuration['retry'] ?? null,
            'response_mapping' => $tool->configuration['response_mapping'] ?? null,
            'headers' => $tool->configuration['headers'] ?? null,
            'query' => $tool->configuration['query'] ?? null,
            'body' => $tool->configuration['body'] ?? null,
            'request_mapping' => $tool->configuration['request_mapping'] ?? null,
            'handler' => $tool->configuration['handler'] ?? null,
            'connection' => $tool->connection,
        ], static fn ($value) => $value !== null && $value !== [] && $value !== '');

        $config = $tool->configuration;
        foreach ([
            'method', 'url', 'auth', 'timeout', 'retry', 'response_mapping', 'headers', 'query', 'body', 'request_mapping',
            'handler', 'connection',
            'input_schema', 'inputSchema', 'output_schema', 'outputSchema', 'permissions', 'runtime', 'approval',
        ] as $key) {
            unset($config[$key]);
        }

        return new self(
            name: $tool->description !== '' ? $tool->description : $tool->name,
            slug: $tool->name,
            driver: (string) $tool->driver,
            description: $tool->description,
            status: $tool->status,
            config: $config,
            definition: $definition,
            id: $tool->id,
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
            id: isset($validated['id']) ? (int) $validated['id'] : null,
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
            'status' => $this->status,
            'driver' => $this->driver,
            'config' => $this->config,
            'definition' => $this->definition,
            'publish' => $this->publish,
        ];
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
