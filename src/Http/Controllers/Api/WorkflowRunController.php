<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Workflow\WorkflowExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowRunController
{
    public function __construct(
        private WorkflowExecutionService $workflows,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_slug' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:32'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $offset = (int) ($validated['offset'] ?? 0);

        $runs = $this->workflows->listRuns(
            $validated['workflow_slug'] ?? null,
            $validated['status'] ?? null,
            $limit,
            $offset,
        );

        return response()->json([
            'data' => array_map(fn ($run) => $run->toArray(), $runs),
            'meta' => [
                'total' => $this->workflows->countRuns(
                    $validated['workflow_slug'] ?? null,
                    $validated['status'] ?? null,
                ),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $run = $this->workflows->showRun($uuid);

        if ($run === null) {
            return response()->json(['message' => 'Workflow run not found.'], 404);
        }

        return response()->json(['data' => $run->toArray()]);
    }
}
