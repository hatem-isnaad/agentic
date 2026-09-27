<?php

namespace Agentic\Mcp;

use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;
use InvalidArgumentException;

final class McpToolSyncService
{
    public function __construct(
        private McpClientGateway $gateway,
        private McpToolRegistrar $registrar,
    ) {}

    /**
     * @return list<string>
     */
    public function sync(string $server, ?string $prefix = null): array
    {
        if (! $this->gateway->hasServer($server)) {
            throw new InvalidArgumentException("MCP server [{$server}] is not configured.");
        }

        $prefix ??= (string) config('agentic.mcp.tool_prefix', '');

        return $this->registrar->registerServer($server, $prefix);
    }

    /**
     * @return list<string>
     */
    public function servers(): array
    {
        $configured = config('mcp.servers', config('agentic.mcp.servers', []));

        if (! is_array($configured)) {
            return [];
        }

        return array_values(array_map('strval', array_keys($configured)));
    }
}
