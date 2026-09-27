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

    /**
     * @return list<array{uri: string, name: string, description: string|null, mime_type: string|null}>
     */
    public function listResources(string $server): array;

    /**
     * @return array{uri: string, mime_type: string|null, text: string|null, blob: string|null}
     */
    public function readResource(string $server, string $uri): array;

    /**
     * @return list<array{name: string, description: string|null, arguments: list<array<string, mixed>>}>
     */
    public function listPrompts(string $server): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{description: string|null, messages: list<array<string, mixed>>}
     */
    public function getPrompt(string $server, string $name, array $arguments = []): array;

    public function hasServer(string $server): bool;
}
