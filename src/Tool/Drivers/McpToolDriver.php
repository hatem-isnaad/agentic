<?php

namespace Agentic\Tool\Drivers;

use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

/**
 * MCP tool driver boundary.
 *
 * MCP protocol execution is delegated to Laravel AI SDK / laravel/mcp.
 * This driver keeps MCP tools addressable through the unified Tool Registry.
 */
final class McpToolDriver implements ToolDriver
{
    public function name(): string
    {
        return 'mcp';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        $server = $tool->definition()->configuration['server'] ?? null;
        $mcpTool = $tool->definition()->configuration['tool'] ?? $tool->definition()->name;

        return ToolResult::failure(sprintf(
            'MCP tool [%s] is not executable yet (server=%s, tool=%s). Wire laravel/mcp in the MCP driver PR.',
            $tool->definition()->name,
            is_string($server) ? $server : 'null',
            is_string($mcpTool) ? $mcpTool : 'null',
        ));
    }
}
