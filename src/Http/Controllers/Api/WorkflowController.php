<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Workflow\WorkflowDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowController
{
    public function __construct(
        private WorkflowRepository $workflows,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (WorkflowDefinition $workflow) => $this->serialize($workflow),
                $this->workflows->allPublished(),
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $workflow = $this->workflows->findBySlug($slug);

        if ($workflow === null) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        return response()->json(['data' => $this->serialize($workflow)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $workflow = $this->workflows->save($validated);

        return response()->json(['data' => $this->serialize($workflow)], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        if ($this->workflows->findBySlug($slug) === null) {
            return response()->json(['message' => 'Workflow not found.'], 404);
        }

        $validated = $this->validatePayload($request, $slug);
        $workflow = $this->workflows->save($validated);

        return response()->json(['data' => $this->serialize($workflow)]);
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
            'steps.*.type' => ['required', 'string', 'in:set,tool,agent,condition,complete'],
        ]);

        if ($slug !== null) {
            $validated['slug'] = $slug;
        }

        $validated['steps'] = $request->input('steps', []);
        $validated['definition'] = ['steps' => $validated['steps']];

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(WorkflowDefinition $workflow): array
    {
        return $workflow->toArray();
    }
}
