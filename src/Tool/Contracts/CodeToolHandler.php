<?php

namespace Agentic\Tool\Contracts;

use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

/**
 * Application-native handler invoked by the Code tool driver.
 *
 * Handlers must be explicitly registered — never loaded from untrusted PHP source.
 */
interface CodeToolHandler
{
    public function handle(ToolExecutionContext $context): ToolResult|array|string|int|float|bool|null;
}
