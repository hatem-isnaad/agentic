<?php

namespace Agentic\Tool\Drivers\Mcp;

use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolFactory;

/**
 * Discovers MCP server tools and registers them in the Agentic Tool Registry.
 */
final class McpToolRegistrar
{
    public function __construct(
        private McpClientGateway $mcp,
        private ToolFactory $factory,
    ) {}

    /**
     * @return list<string> Registered tool names
     */
    public function registerServer(string $server, string $prefix = ''): array
    {
        $registered = [];

        foreach ($this->mcp->listTools($server) as $tool) {
            $name = $prefix !== '' ? $prefix.$tool['name'] : $tool['name'];

            $this->factory->register(new ToolDefinition(
                name: $name,
                description: $tool['description'],
                inputSchema: $tool['input_schema'],
                outputSchema: $tool['output_schema'] ?? [],
                driver: 'mcp',
                configuration: [
                    'server' => $server,
                    'tool' => $tool['name'],
                ],
            ));

            $registered[] = $name;
        }

        return $registered;
    }
}
