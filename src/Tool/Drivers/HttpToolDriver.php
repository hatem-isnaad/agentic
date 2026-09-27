<?php

namespace Agentic\Tool\Drivers;

use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

/**
 * HTTP tool driver boundary.
 *
 * Full request builder / auth / mapping land in a dedicated HTTP driver PR.
 * Until then, Tools that implement ToolContract::execute remain callable.
 */
final class HttpToolDriver implements ToolDriver
{
    public function name(): string
    {
        return 'http';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        return $tool->execute($context);
    }
}
