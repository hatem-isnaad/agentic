<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\AgentRepository;
use Illuminate\Http\JsonResponse;
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
            'model' => $agent->model,
            'provider' => $agent->provider,
            'skills' => $agent->skills,
            'tools' => $agent->tools,
            'status' => $agent->status,
        ];
    }
}
