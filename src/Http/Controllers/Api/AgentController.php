<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\AgentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentController
{
    public function __construct(
        private AgentRepository $agents,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (AgentDefinition $agent) => $this->serializeAgent($agent),
                $this->agents->allPublished(),
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $agent = $this->agents->findBySlug($slug);

        if ($agent === null) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }

        return response()->json(['data' => $this->serializeAgent($agent)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $agent = $this->agents->save($validated);

        return response()->json(['data' => $this->serializeAgent($agent)], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        if ($this->agents->findBySlug($slug) === null) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }

        $validated = $this->validatePayload($request, $slug);

        $agent = $this->agents->save($validated);

        return response()->json(['data' => $this->serializeAgent($agent)]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (! $this->agents->delete($slug)) {
            return response()->json(['message' => 'Agent not found.'], 404);
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
            'provider' => ['nullable', 'string', 'max:128'],
            'model' => ['nullable', 'string', 'max:128'],
            'temperature' => ['nullable', 'numeric'],
            'max_tokens' => ['nullable', 'integer', 'min:1'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'knowledge' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'runtime' => ['nullable', 'array'],
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
    private function serializeAgent(AgentDefinition $agent): array
    {
        return [
            'id' => $agent->id,
            'slug' => $agent->slug ?? $agent->identifier(),
            'name' => $agent->name,
            'description' => $agent->description,
            'instructions' => $agent->instructions,
            'model' => $agent->model,
            'provider' => $agent->provider,
            'temperature' => $agent->temperature,
            'max_tokens' => $agent->maxTokens,
            'skills' => $agent->skills,
            'tools' => $agent->tools,
            'knowledge' => $agent->knowledge,
            'permissions' => $agent->permissions,
            'status' => $agent->status,
        ];
    }
}
