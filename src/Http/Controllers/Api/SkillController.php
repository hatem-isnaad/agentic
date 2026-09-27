<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Skill\SkillDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SkillController
{
    public function __construct(
        private SkillRepository $skills,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (SkillDefinition $skill) => $this->serialize($skill),
                $this->skills->allPublished(),
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $skill = $this->skills->findBySlug($slug);

        if ($skill === null) {
            return response()->json(['message' => 'Skill not found.'], 404);
        }

        return response()->json(['data' => $this->serialize($skill)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $skill = $this->skills->save($validated);

        return response()->json(['data' => $this->serialize($skill)], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        if ($this->skills->findBySlug($slug) === null) {
            return response()->json(['message' => 'Skill not found.'], 404);
        }

        $validated = $this->validatePayload($request, $slug);

        $skill = $this->skills->save($validated);

        return response()->json(['data' => $this->serialize($skill)]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->skills->delete($slug)) {
            return response()->json(['message' => 'Skill not found.'], 404);
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
            'instructions' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'knowledge' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
        ]);

        if ($slug !== null) {
            $validated['slug'] = $slug;
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SkillDefinition $skill): array
    {
        return [
            'name' => $skill->metadata['display_name'] ?? $skill->name,
            'slug' => $skill->name,
            'description' => $skill->description,
            'instructions' => $skill->metadata['instructions'] ?? null,
            'tools' => $skill->tools,
            'knowledge' => $skill->knowledge,
            'status' => $skill->metadata['status'] ?? null,
            'id' => $skill->metadata['id'] ?? null,
        ];
    }
}
