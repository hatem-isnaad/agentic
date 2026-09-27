<?php

namespace Agentic\Tool;

use Agentic\Models\ToolApproval;
use Agentic\Tool\Contracts\ToolContract;
use Illuminate\Support\Str;

final class ToolApprovalService
{
    public function requiresApproval(ToolContract $tool): bool
    {
        if (! config('agentic.tool_approval.enabled', true)) {
            return false;
        }

        $definition = $tool->definition();
        $config = $definition->configuration;

        if (($config['requires_approval'] ?? false) === true) {
            return true;
        }

        if (($config['writes'] ?? false) === true) {
            return true;
        }

        $patterns = config('agentic.tool_approval.tool_patterns', []);

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $definition->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
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
