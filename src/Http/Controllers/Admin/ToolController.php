<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\ToolAdminService;
use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Http\Requests\Admin\StoreToolRequest;
use Agentic\Http\Requests\Admin\UpdateToolRequest;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Tool\HttpToolTester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class ToolController
{
    public function __construct(
        private ToolAdminService $tools,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = array_map(fn ($tool) => $tool->toArray(), $this->tools->list());

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function show(string $slug): JsonResponse
    {
        $tool = $this->tools->find($slug);

        if ($tool === null) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        return response()->json(['data' => $tool->toArray(), 'meta' => AdminLocaleMeta::build()]);
    }

    public function store(StoreToolRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->tools->store($request->toData())->toArray()], 201);
    }

    public function update(UpdateToolRequest $request, string $slug): JsonResponse
    {
        if ($this->tools->find($slug) === null) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        return response()->json(['data' => $this->tools->update($slug, $request->toData())->toArray()]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->tools->delete($slug)) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        return response()->json(null, 204);
    }

    public function clone(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            return response()->json([
                'data' => $this->tools->clone(
                    $slug,
                    $validated['name'] ?? null,
                    $validated['slug'] ?? null,
                )->toArray(),
            ], 201);
        } catch (ToolNotFoundException) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }
    }

    public function test(Request $request, string $slug, HttpToolTester $tester): JsonResponse
    {
        $validated = $request->validate([
            'arguments' => ['nullable', 'array'],
        ]);

        try {
            return response()->json(['data' => $tester->run($slug, $validated['arguments'] ?? [])]);
        } catch (ToolNotFoundException) {
            return response()->json(['message' => 'Tool not found.'], 404);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
