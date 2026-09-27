<?php

namespace Agentic\Tool;

use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;

/**
 * Tool backed by a ToolDefinition and executed through a ToolDriver.
 *
 * Used for DB/UI-defined HTTP, Code, and MCP tools.
 */
final class ConfiguredTool implements ToolContract
{
    public function __construct(
        private ToolDefinition $definition,
        private DriverResolver $drivers,
    ) {}

    public function definition(): ToolDefinition
    {
        return $this->definition;
    }

    public function execute(ToolExecutionContext $context): ToolResult
    {
        $driverName = $this->definition->driver ?? 'code';

        /** @var ToolDriver $driver */
        $driver = $this->drivers->resolve($driverName);

        return $driver->execute($this, $context);
    }
}
