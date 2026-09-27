<?php

namespace Agentic\Tool\Drivers;

use Agentic\Exceptions\InvalidToolInputException;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

/**
 * Executes MCP-backed tools through the McpClientGateway.
 *
 * MCP protocol details stay in laravel/mcp.
 */
final class McpToolDriver implements ToolDriver
{
    public function __construct(
        private McpClientGateway $mcp,
    ) {}

    public function name(): string
    {
        return 'mcp';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        $definition = $tool->definition();
        $config = $definition->configuration;

        $server = $config['server'] ?? null;
        $mcpTool = $config['tool'] ?? $definition->name;

        if (! is_string($server) || $server === '') {
            return ToolResult::failure(
                "MCP tool [{$definition->name}] requires configuration.server."
            );
        }

        if (! is_string($mcpTool) || $mcpTool === '') {
            return ToolResult::failure(
                "MCP tool [{$definition->name}] requires configuration.tool."
            );
        }

        try {
            $this->validateInput($definition->name, $definition->inputSchema, $context->arguments);
        } catch (InvalidToolInputException $exception) {
            return ToolResult::failure($exception->getMessage());
        }

        if (! $this->mcp->hasServer($server)) {
            return ToolResult::failure(
                "MCP server [{$server}] is not registered."
            );
        }

        $result = $this->mcp->callTool($server, $mcpTool, $context->arguments);

        if (! $result['success']) {
            return ToolResult::failure(
                $result['error'] ?? "MCP tool [{$mcpTool}] failed."
            );
        }

        return ToolResult::success($result['data']);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $arguments
     */
    private function validateInput(string $tool, array $schema, array $arguments): void
    {
        if ($schema === []) {
            return;
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : $schema;
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        foreach ($properties as $name => $definition) {
            if (! is_string($name)) {
                continue;
            }

            $isRequired = in_array($name, $required, true)
                || (is_array($definition) && ($definition['required'] ?? false) === true);

            if ($isRequired && ! array_key_exists($name, $arguments)) {
                throw new InvalidToolInputException($tool, "Missing required argument [{$name}].");
            }
        }
    }
}
