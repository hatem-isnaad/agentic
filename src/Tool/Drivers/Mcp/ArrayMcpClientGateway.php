<?php

namespace Agentic\Tool\Drivers\Mcp;

use Agentic\Tool\Contracts\McpClientGateway;

/**
 * In-memory MCP gateway for tests and local stubs.
 */
final class ArrayMcpClientGateway implements McpClientGateway
{
    /**
     * @param  array<string, array{tools?: list<array<string, mixed>>, resources?: list<array<string, mixed>>, prompts?: list<array<string, mixed>>}>  $servers
     */
    public function __construct(
        private array $servers = [],
    ) {}

    /**
     * @param  list<array{name: string, description?: string, input_schema?: array<string, mixed>, output_schema?: array<string, mixed>|null, handler?: callable}>  $tools
     */
    /**
     * @param  list<array<string, mixed>>  $tools
     */
    public function registerServer(string $server, array $tools): void
    {
        $this->servers[$server] = ['tools' => $tools];
    }

    /**
     * @param  array{tools?: list<array<string, mixed>>, resources?: list<array<string, mixed>>, prompts?: list<array<string, mixed>>}  $catalog
     */
    public function registerCatalog(string $server, array $catalog): void
    {
        $this->servers[$server] = $catalog;
    }

    public function hasServer(string $server): bool
    {
        return isset($this->servers[$server]);
    }

    public function listTools(string $server): array
    {
        $tools = $this->servers[$server]['tools'] ?? $this->servers[$server] ?? [];

        return array_map(fn (array $tool) => [
            'name' => $tool['name'],
            'description' => $tool['description'] ?? $tool['name'],
            'input_schema' => $tool['input_schema'] ?? [],
            'output_schema' => $tool['output_schema'] ?? null,
        ], is_array($tools) ? $tools : []);
    }

    public function listResources(string $server): array
    {
        return array_values($this->servers[$server]['resources'] ?? []);
    }

    public function readResource(string $server, string $uri): array
    {
        foreach ($this->servers[$server]['resources'] ?? [] as $resource) {
            if (($resource['uri'] ?? '') !== $uri) {
                continue;
            }

            return [
                'uri' => $uri,
                'mime_type' => $resource['mime_type'] ?? 'text/plain',
                'text' => (string) ($resource['text'] ?? ''),
                'contents' => [],
            ];
        }

        return [
            'uri' => $uri,
            'mime_type' => null,
            'text' => null,
            'contents' => [],
        ];
    }

    public function listPrompts(string $server): array
    {
        return array_values($this->servers[$server]['prompts'] ?? []);
    }

    public function getPrompt(string $server, string $name, array $arguments = []): array
    {
        foreach ($this->servers[$server]['prompts'] ?? [] as $prompt) {
            if (($prompt['name'] ?? '') !== $name) {
                continue;
            }

            $handler = $prompt['handler'] ?? null;

            if (is_callable($handler)) {
                return $handler($arguments);
            }

            return [
                'description' => $prompt['description'] ?? null,
                'messages' => $prompt['messages'] ?? [],
            ];
        }

        return [
            'description' => null,
            'messages' => [],
        ];
    }

    public function callTool(string $server, string $tool, array $arguments = []): array
    {
        foreach ($this->servers[$server]['tools'] ?? [] as $definition) {
            if ($definition['name'] !== $tool) {
                continue;
            }

            if (! isset($definition['handler']) || ! is_callable($definition['handler'])) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => "MCP tool [{$tool}] has no handler.",
                ];
            }

            $data = ($definition['handler'])($arguments);

            return [
                'success' => true,
                'data' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'data' => null,
            'error' => "MCP tool [{$tool}] was not found on server [{$server}].",
        ];
    }
}
