<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Workflow\WorkflowExecutionService;
use Illuminate\Http\JsonResponse;

final class WorkflowRunController
{
    public function __construct(
        private WorkflowExecutionService $workflows,
    ) {}

    public function show(string $uuid): JsonResponse
    {
        $run = $this->workflows->showRun($uuid);

        if ($run === null) {
            return response()->json(['message' => 'Workflow run not found.'], 404);
        }

        return response()->json([
            'data' => [
                'uuid' => $run->uuid,
                'workflow_slug' => $run->workflowSlug,
                'status' => $run->status,
                'step_pointer' => $run->stepPointer,
                'approval_id' => $run->approvalUuid,
                'variables' => $run->variables,
                'trace' => $run->trace,
                'output' => $run->output,
                'error' => $run->error,
            ],
        ]);
    }
}
