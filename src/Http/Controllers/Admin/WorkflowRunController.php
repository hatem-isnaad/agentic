<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min(100, max(1, (int) ($validated['per_page'] ?? 25)));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $workflowSlug = $validated['workflow_slug'] ?? null;
        $status = $validated['status'] ?? null;

        $items = array_map(
            fn ($run) => $run->toArray(),
            $this->workflows->listRuns($workflowSlug, $status, $perPage, $offset),
        );

        $total = $this->workflows->countRuns($workflowSlug, $status);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return response()->json([
            'data' => $items,
            'meta' => AdminLocaleMeta::build([
                'total' => $total,
                'page' => min($page, $lastPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ]),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $run = $this->workflows->showRun($uuid);

        if ($run === null) {
            return response()->json(['message' => 'Workflow run not found.'], 404);
        }

        return response()->json([
            'data' => $run->toArray(),
            'meta' => AdminLocaleMeta::build(),
        ]);
    }
}
