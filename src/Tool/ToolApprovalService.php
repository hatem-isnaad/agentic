<?php

namespace Agentic\Tool;

use Agentic\Tool\Contracts\ToolContract;
use Laravel\Ai\Approvals\Approval;

final class ToolApprovalService
{
    public function decision(ToolContract $tool): Approval|bool
    {
        $definition = $tool->definition();
        $policy = $definition->approval
            ?? config('agentic.approvals.default', 'never');

        if ($policy === 'always') {
            return Approval::required(
                (string) config(
                    'agentic.approvals.reason',
                    'This tool requires human approval before execution.',
                ),
            );
        }

        return false;
    }

    public function requiresApproval(ToolContract $tool): bool
    {
        return $this->decision($tool) !== false;
    }
}
