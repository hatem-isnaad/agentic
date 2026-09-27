<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ExecutionAdminService;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExecutionController
{
    public function __construct(
        private ExecutionAdminService $executions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = min(500, max(1, (int) $request->query('fetch_limit', 200)));
        $items = array_map(fn ($row) => $row->toArray(), $this->executions->list($limit));

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function show(string $id): JsonResponse
    {
        $execution = $this->executions->find($id);

        if ($execution === null) {
            return response()->json(['message' => 'Execution not found.'], 404);
        }

        return response()->json(['data' => $execution->toArray(), 'meta' => AdminLocaleMeta::build()]);
    }
}
