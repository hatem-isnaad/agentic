<?php

namespace Agentic\Tool\Drivers\Mcp;

use Agentic\Tool\Contracts\McpClientGateway;

/**
 * In-memory MCP gateway for tests and local stubs.
 */
final class ArrayMcpClientGateway implements McpClientGateway
{
    /**
     * @param  array<string, list<array{name: string, description?: string, input_schema?: array<string, mixed>, output_schema?: array<string, mixed>|null, handler?: callable}>>  $servers
     */
    public function __construct(
        private array $servers = [],
    ) {}

    /**
     * @param  list<array{name: string, description?: string, input_schema?: array<string, mixed>, output_schema?: array<string, mixed>|null, handler?: callable}>  $tools
     */
    public function registerServer(string $server, array $tools): void
    {
        $this->servers[$server] = $tools;
    }

    public function hasServer(string $server): bool
    {
        return isset($this->servers[$server]);
    }

    public function listTools(string $server): array
    {
        return array_map(fn (array $tool) => [
            'name' => $tool['name'],
            'description' => $tool['description'] ?? $tool['name'],
            'input_schema' => $tool['input_schema'] ?? [],
            'output_schema' => $tool['output_schema'] ?? null,
        ], $this->servers[$server] ?? []);
    }

    public function callTool(string $server, string $tool, array $arguments = []): array
    {
        foreach ($this->servers[$server] ?? [] as $definition) {
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
