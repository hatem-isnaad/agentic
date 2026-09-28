<?php

namespace Agentic\Tool;

use Agentic\Models\ToolApproval;
use Agentic\Tool\Contracts\ToolContract;
use Illuminate\Support\Str;
use Laravel\Ai\Approvals\Approval;

final class ToolApprovalService
{
    public function decision(ToolContract $tool): Approval|bool
    {
        $definition = $tool->definition();
        $policy = $definition->approval
            ?? config('agentic.approvals.default', 'never');

        if ($policy === 'always' || $this->matchesWidgetPattern($tool)) {
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

    public function matchesWidgetPattern(ToolContract $tool): bool
    {
        if (! config('agentic.tool_approval.enabled', true)) {
            return false;
        }

        $definition = $tool->definition();
        $config = $definition->configuration;

        if (($config['requires_approval'] ?? false) === true || ($config['writes'] ?? false) === true) {
            return true;
        }

        foreach (config('agentic.tool_approval.tool_patterns', []) as $pattern) {
            if (is_string($pattern) && $pattern !== '' && fnmatch($pattern, $definition->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createWorkflowPending(
        string $workflowSlug,
        string $stepId,
        array $payload = [],
        ?string $title = null,
        ?string $message = null,
    ): ToolApproval {
        return ToolApproval::query()->create([
            'uuid' => (string) Str::uuid(),
            'agent' => 'workflow:'.$workflowSlug,
            'tool' => 'workflow:'.$workflowSlug.':'.$stepId,
            'arguments' => $payload,
            'status' => 'pending',
            'metadata' => [
                'kind' => 'workflow',
                'workflow' => $workflowSlug,
                'step' => $stepId,
                'title' => $title,
                'message' => $message,
            ],
        ]);
    }

    public function createPending(
        ToolContract $tool,
        array $arguments,
        ?string $executionUuid = null,
        ?string $conversationUuid = null,
        ?string $agent = null,
        array $metadata = [],
    ): ToolApproval {
        return ToolApproval::query()->create([
            'uuid' => (string) Str::uuid(),
            'execution_uuid' => $executionUuid,
            'conversation_uuid' => $conversationUuid,
            'agent' => $agent ?? '',
            'tool' => $tool->definition()->name,
            'arguments' => $arguments,
            'status' => 'pending',
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public function find(string $uuid): ?ToolApproval
    {
        return ToolApproval::query()->where('uuid', $uuid)->first();
    }

    public function approve(ToolApproval $approval, ?string $resolvedBy = null): ToolApproval
    {
        $approval->fill([
            'status' => 'approved',
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ])->save();

        return $approval->fresh();
    }

    public function reject(ToolApproval $approval, ?string $resolvedBy = null): ToolApproval
    {
        $approval->fill([
            'status' => 'rejected',
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ])->save();

        return $approval->fresh();
    }

    public function isApprovedFor(ToolContract $tool, ToolExecutionContext $context): bool
    {
        $approvalId = $context->metadata['approval_id'] ?? null;

        if (! is_string($approvalId) || $approvalId === '') {
            return false;
        }

        $approval = $this->find($approvalId);

        return $approval !== null
            && $approval->status === 'approved'
            && $approval->tool === $tool->definition()->name;
    }
}
