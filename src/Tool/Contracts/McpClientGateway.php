<?php

namespace Agentic\Tool\Contracts;

/**
 * Boundary for MCP protocol clients.
 *
 * Implementations delegate to laravel/mcp — Agentic does not speak MCP itself.
 */
interface McpClientGateway
{
    /**
     * @return list<array{
     *     name: string,
     *     description: string,
     *     input_schema: array<string, mixed>,
     *     output_schema: array<string, mixed>|null
     * }>
     */
    public function listTools(string $server): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{success: bool, data: mixed, error: string|null}
     */
    public function callTool(string $server, string $tool, array $arguments = []): array;

    public function hasServer(string $server): bool;
}
