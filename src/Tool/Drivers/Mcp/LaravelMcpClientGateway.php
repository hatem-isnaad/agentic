<?php

namespace Agentic\Tool\Drivers\Mcp;

use Agentic\Tool\Contracts\McpClientGateway;
use Laravel\Mcp\Client\ClientManager;
use Laravel\Mcp\Client\Schema\ToolResult;
use Laravel\Mcp\Exceptions\ClientException;
use Throwable;

/**
 * MCP client gateway backed by laravel/mcp ClientManager.
 */
final class LaravelMcpClientGateway implements McpClientGateway
{
    public function __construct(
        private ClientManager $clients,
    ) {}

    public function hasServer(string $server): bool
    {
        try {
            $this->clients->build($server);

            return true;
        } catch (ClientException) {
            return false;
        }
    }

    public function listTools(string $server): array
    {
        $client = $this->clients->client($server);

        if (! $client->connected()) {
            $client->connect();
        }

        return $client->tools()->map(fn ($tool) => [
            'name' => $tool->name,
            'description' => (string) ($tool->description ?? $tool->title ?? $tool->name),
            'input_schema' => $tool->inputSchema,
            'output_schema' => $tool->outputSchema,
        ])->values()->all();
    }

    public function callTool(string $server, string $tool, array $arguments = []): array
    {
        try {
            $client = $this->clients->client($server);

            if (! $client->connected()) {
                $client->connect();
            }

            /** @var ToolResult $result */
            $result = $client->callTool($tool, $arguments);

            if ($result->isError) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => $result->text() !== '' ? $result->text() : 'MCP tool returned an error.',
                ];
            }

            $data = $result->structuredContent ?? $result->text();

            return [
                'success' => true,
                'data' => $data,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'data' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
