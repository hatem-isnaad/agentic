<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\WorkflowAdminService;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowController
{
    public function __construct(
        private WorkflowAdminService $workflows,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = array_map(
            fn ($workflow) => $workflow->toArray(),
            $this->workflows->list(),
        );

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function show(string $slug): JsonResponse
    {
        $workflow = $this->workflows->find($slug);

        if ($workflow === null) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        return response()->json(['data' => $workflow->toArray(), 'meta' => AdminLocaleMeta::build()]);
    }

    public function store(Request $request): JsonResponse
    {
        $workflow = $this->workflows->store($this->validatePayload($request));

        return response()->json(['data' => $workflow->toArray()], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        if ($this->workflows->find($slug) === null) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        $workflow = $this->workflows->update($slug, $this->validatePayload($request, $slug));

        return response()->json(['data' => $workflow->toArray()]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->workflows->delete($slug)) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?string $slug = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.id' => ['required', 'string', 'max:191'],
            'steps.*.type' => ['required', 'string', 'in:set,tool,agent,condition,parallel,approval,complete'],
        ]);

        if ($slug !== null) {
            $validated['slug'] = $slug;
        }

        $validated['steps'] = $request->input('steps', []);
        $validated['definition'] = ['steps' => $validated['steps']];

        return $validated;
    }
}
