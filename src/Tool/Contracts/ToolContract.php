<?php

namespace Agentic\Tool\Contracts;

use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

interface ToolContract
{
    public function definition(): ToolDefinition;

    public function execute(ToolExecutionContext $context): ToolResult;
}
