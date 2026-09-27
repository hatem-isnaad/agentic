<?php

namespace Agentic\Tool\Drivers;

use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;
use RuntimeException;

/**
 * MCP tool driver boundary.
 *
 * MCP protocol execution is delegated to Laravel AI SDK / laravel/mcp.
 * This driver establishes the Agentic integration surface for the Tool Registry.
 */
final class McpToolDriver implements ToolDriver
{
    public function name(): string
    {
        return 'mcp';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        throw new RuntimeException(
            'MCP tool execution is not implemented yet. Register an MCP-backed ToolContract or wait for the MCP driver PR.'
        );
    }
}
