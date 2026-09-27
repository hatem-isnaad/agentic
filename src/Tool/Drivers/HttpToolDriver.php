<?php

namespace Agentic\Tool\Drivers;

use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class HttpToolDriver
{
    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        return $tool->execute($context);
    }
}
