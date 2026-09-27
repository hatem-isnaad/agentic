<?php

namespace Agentic\Tool\Contracts;

use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

/**
 * Strategy for executing a Tool.
 *
 * Drivers must not orchestrate Agents — only execute and return a result.
 */
interface ToolDriver
{
    public function name(): string;

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult;
}
