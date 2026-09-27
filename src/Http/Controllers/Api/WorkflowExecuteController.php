<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Exceptions\WorkflowNotFoundException;
use Agentic\Workflow\WorkflowExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowExecuteController
{
    public function __construct(
        private WorkflowExecutionService $workflows,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        try {
            $validated = $request->validate([
                'input' => ['nullable', 'array'],
            ]);

            $result = $this->workflows->execute($slug, $validated['input'] ?? []);
        } catch (WorkflowNotFoundException) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        return $this->respond($result);
    }

    private function respond(\Agentic\Workflow\WorkflowResult $result): JsonResponse
    {
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
