<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\AgentAdminService;
use Agentic\Http\Requests\Admin\StoreAgentRequest;
use Agentic\Http\Requests\Admin\UpdateAgentRequest;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentController
{
    public function __construct(
        private AgentAdminService $agents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = array_map(fn ($agent) => $agent->toArray(), $this->agents->list());
        $paginated = AdminPaginator::paginate($items, $request);

        return response()->json($paginated);
    }

    public function show(string $slug): JsonResponse
    {
        $agent = $this->agents->find($slug);

        if ($agent === null) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }

        return response()->json([
            'data' => $agent->toArray(),
            'meta' => AdminLocaleMeta::build(),
        ]);
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $agent = $this->agents->store($request->toData());

        return response()->json(['data' => $agent->toArray()], 201);
    }

    public function update(UpdateAgentRequest $request, string $slug): JsonResponse
    {
        if ($this->agents->find($slug) === null) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }

        $agent = $this->agents->update($slug, $request->toData());

        return response()->json(['data' => $agent->toArray()]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->agents->delete($slug)) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }

        return response()->json(null, 204);
    }
}
