<?php

namespace Agentic\Mcp;

use Agentic\Tool\Contracts\McpClientGateway;
use InvalidArgumentException;

final class McpServerService
{
    public function __construct(
        private McpClientGateway $gateway,
        private McpToolSyncService $sync,
    ) {}

    /**
     * @return list<string>
     */
    public function servers(): array
    {
        return $this->sync->servers();
    }

    /**
     * @return list<string>
     */
    public function syncTools(string $server, ?string $prefix = null): array
    {
        return $this->sync->sync($server, $prefix);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tools(string $server): array
    {
        $this->assertServer($server);

        return $this->gateway->listTools($server);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resources(string $server): array
    {
        $this->assertServer($server);

        return $this->gateway->listResources($server);
    }

    /**
     * @return array<string, mixed>
     */
    public function readResource(string $server, string $uri): array
    {
        $this->assertServer($server);

        return $this->gateway->readResource($server, $uri);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function prompts(string $server): array
    {
        $this->assertServer($server);

        return $this->gateway->listPrompts($server);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function prompt(string $server, string $name, array $arguments = []): array
    {
        $this->assertServer($server);

        return $this->gateway->getPrompt($server, $name, $arguments);
    }

    private function assertServer(string $server): void
    {
        if (! $this->gateway->hasServer($server)) {
            throw new InvalidArgumentException("MCP server [{$server}] is not configured.");
        }
    }
}
