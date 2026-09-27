<?php

namespace Agentic\Tool\Drivers;

use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

/**
 * Code tool driver boundary.
 *
 * Must never evaluate arbitrary untrusted PHP from UI configuration.
 * Resolves registered handlers / callables only (full resolver in Code driver PR).
 */
final class CodeToolDriver implements ToolDriver
{
    public function name(): string
    {
        return 'code';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        return $tool->execute($context);
    }
}
