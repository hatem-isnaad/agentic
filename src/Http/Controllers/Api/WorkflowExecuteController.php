<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Exceptions\WorkflowNotFoundException;
use Agentic\Workflow\WorkflowResolver;
use Agentic\Workflow\WorkflowRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowExecuteController
{
    public function __construct(
        private WorkflowResolver $workflows,
        private WorkflowRunner $runner,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        try {
            $workflow = $this->workflows->resolve($slug);
        } catch (WorkflowNotFoundException) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        $validated = $request->validate([
            'input' => ['nullable', 'array'],
        ]);

        $result = $this->runner->run($workflow, $validated['input'] ?? []);

        if ($result->pending) {
            return response()->json([
                'data' => [
                    'status' => 'pending_approval',
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
                'trace' => $result->trace,
            ], 422);
        }

        return response()->json([
            'data' => [
                'output' => $result->output,
                'variables' => $result->variables,
                'trace' => $result->trace,
            ],
        ]);
    }
}
