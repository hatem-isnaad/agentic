<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Admin\Services\SkillAdminService;
use Agentic\Http\Requests\Admin\StoreSkillRequest;
use Agentic\Http\Requests\Admin\UpdateSkillRequest;
use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SkillController
{
    public function __construct(
        private SkillAdminService $skills,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = array_map(fn ($skill) => $skill->toArray(), $this->skills->list());

        return response()->json(AdminPaginator::paginate($items, $request));
    }

    public function show(string $slug): JsonResponse
    {
        $skill = $this->skills->find($slug);

        if ($skill === null) {
            return response()->json(['message' => 'Skill not found.'], 404);
        }

        return response()->json(['data' => $skill->toArray(), 'meta' => AdminLocaleMeta::build()]);
    }

    public function store(StoreSkillRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->skills->store($request->toData())->toArray()], 201);
    }

    public function update(UpdateSkillRequest $request, string $slug): JsonResponse
    {
        if ($this->skills->find($slug) === null) {
            return response()->json(['message' => 'Skill not found.'], 404);
        }

        return response()->json(['data' => $this->skills->update($slug, $request->toData())->toArray()]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->skills->delete($slug)) {
            return response()->json(['message' => 'Skill not found.'], 404);
        }

        return response()->json(null, 204);
    }
}
