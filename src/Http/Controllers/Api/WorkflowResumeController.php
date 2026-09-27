<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Exceptions\WorkflowNotFoundException;
use Agentic\Tool\ToolApprovalService;
use Agentic\Workflow\WorkflowResolver;
use Agentic\Workflow\WorkflowRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowResumeController
{
    public function __construct(
        private WorkflowResolver $workflows,
        private WorkflowRunner $runner,
        private ToolApprovalService $approvals,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        try {
            $workflow = $this->workflows->resolve($slug);
        } catch (WorkflowNotFoundException) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        $validated = $request->validate([
            'approval_id' => ['required', 'string', 'uuid'],
            'input' => ['nullable', 'array'],
            'resume_key' => ['nullable', 'string', 'max:64'],
        ]);

        $approval = $this->approvals->find($validated['approval_id']);

        if ($approval === null) {
            return response()->json(['message' => 'Approval not found.'], 404);
        }

        if ($approval->status !== 'approved') {
            return response()->json([
                'message' => 'Workflow cannot resume until the approval is approved.',
                'status' => $approval->status,
            ], 422);
        }

        if (! str_starts_with($approval->tool, 'workflow:'.$workflow->slug.':')) {
            return response()->json(['message' => 'Approval does not belong to this workflow.'], 422);
        }

        $resumeKey = (string) ($validated['resume_key'] ?? '_resume_approval_id');
        $input = array_merge($validated['input'] ?? [], [$resumeKey => $validated['approval_id']]);

        $result = $this->runner->run($workflow, $input);

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
