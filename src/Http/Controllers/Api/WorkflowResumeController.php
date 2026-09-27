<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Exceptions\WorkflowNotFoundException;
use Agentic\Workflow\WorkflowExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowResumeController
{
    public function __construct(
        private WorkflowExecutionService $workflows,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'approval_id' => ['required', 'string', 'uuid'],
            'input' => ['nullable', 'array'],
            'resume_key' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $result = $this->workflows->resume(
                $slug,
                $validated['approval_id'],
                $validated['input'] ?? [],
                $validated['resume_key'] ?? null,
            );
        } catch (WorkflowNotFoundException) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        if ($result->pending) {
            return response()->json([
                'data' => [
                    'status' => 'pending_approval',
                    'workflow_run_id' => $result->runId,
                    'approval_id' => $result->approvalId,
                    'output' => $result->output,
                    'variables' => $result->variables,
                    'trace' => $result->trace,
                ],
            ], 202);
        }

        if (! $result->success) {
            return response()->json([
                'message' => $result->error ?? 'Workflow execution failed.',
                'workflow_run_id' => $result->runId,
                'trace' => $result->trace,
            ], 422);
        }

        return response()->json([
            'data' => [
                'workflow_run_id' => $result->runId,
                'output' => $result->output,
                'variables' => $result->variables,
                'trace' => $result->trace,
            ],
        ]);
    }
}
