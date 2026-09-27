<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Tool\ToolDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ToolController
{
    public function __construct(
        private ToolRepository $tools,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (ToolDefinition $tool) => $this->serialize($tool),
                $this->tools->allPublished(),
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $tool = $this->tools->findBySlug($slug);

        if ($tool === null) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        return response()->json(['data' => $this->serialize($tool)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $tool = $this->tools->save($validated);

        return response()->json(['data' => $this->serialize($tool)], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        if ($this->tools->findBySlug($slug) === null) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        $validated = $this->validatePayload($request, $slug);

        $tool = $this->tools->save($validated);

        return response()->json(['data' => $this->serialize($tool)]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->tools->delete($slug)) {
            return response()->json(['message' => 'Tool not found.'], 404);
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
            'driver' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'config' => ['nullable', 'array'],
            'definition' => ['nullable', 'array'],
            'publish' => ['nullable', 'boolean'],
        ]);

        if ($slug !== null) {
            $validated['slug'] = $slug;
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ToolDefinition $tool): array
    {
        return [
            'slug' => $tool->name,
            'description' => $tool->description,
            'driver' => $tool->driver,
            'input_schema' => $tool->inputSchema,
            'output_schema' => $tool->outputSchema,
            'configuration' => $tool->configuration,
            'permissions' => $tool->permissions,
            'status' => $tool->status,
        ];
    }
}
