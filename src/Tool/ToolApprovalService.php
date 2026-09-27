<?php

namespace Agentic\Tool;

use Agentic\Tool\Contracts\ToolContract;

final class ToolApprovalService
{
    public function requiresApproval(ToolContract $tool): bool
    {
        $name = strtolower($tool->definition()->name);

        return str_contains($name, '.write')
            || str_contains($name, '.create')
            || str_contains($name, '.update')
            || str_contains($name, '.delete')
            || str_contains($name, '.destroy');
    }
}
