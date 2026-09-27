<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Tool\ToolApprovalExecutionService;
use Agentic\Tool\ToolApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApprovalController
{
    public function __construct(
        private ToolApprovalService $approvals,
        private ToolApprovalExecutionService $executor,
    ) {}

    public function approve(Request $request, string $id): JsonResponse
    {
        $approval = $this->approvals->find($id);

        if ($approval === null) {
            return response()->json(['message' => 'Approval not found.'], 404);
        }

        $approval = $this->approvals->approve($approval, $request->user()?->getAuthIdentifier());

        $data = [
            'id' => $approval->uuid,
            'status' => $approval->status,
            'tool' => $approval->tool,
            'arguments' => $approval->arguments,
        ];

        if (config('agentic.tool_approval.auto_execute_on_approve', true)) {
            $data['execution'] = $this->executor->execute($approval->uuid);
        }

        return response()->json(['data' => $data]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $approval = $this->approvals->find($id);

        if ($approval === null) {
            return response()->json(['message' => 'Approval not found.'], 404);
        }

        $approval = $this->approvals->reject($approval, $request->user()?->getAuthIdentifier());

        return response()->json([
            'data' => [
                'id' => $approval->uuid,
                'status' => $approval->status,
                'tool' => $approval->tool,
            ],
        ]);
    }

    public function execute(string $id): JsonResponse
    {
        return response()->json(['data' => $this->executor->execute($id)]);
    }
}
