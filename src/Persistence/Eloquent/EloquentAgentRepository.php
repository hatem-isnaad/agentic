<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Enums\Status;
use Agentic\Models\Agent;

final class EloquentAgentRepository implements AgentRepository
{
    public function findById(int|string $id): ?AgentDefinition
    {
        $agent = Agent::query()->with(['skills' => fn ($q) => $q->where('status', Status::Published)])->find($id);

        return $agent ? $this->toDefinition($agent) : null;
    }

    public function findBySlug(string $slug): ?AgentDefinition
    {
        $agent = Agent::query()
            ->with(['skills' => fn ($q) => $q->where('status', Status::Published)])
            ->where('slug', $slug)
            ->first();

        return $agent ? $this->toDefinition($agent) : null;
    }

    public function allPublished(): array
    {
        return Agent::query()
            ->published()
            ->with(['skills' => fn ($q) => $q->where('status', Status::Published)])
            ->get()
            ->map(fn (Agent $agent) => $this->toDefinition($agent))
            ->all();
    }

    private function toDefinition(Agent $agent): AgentDefinition
    {
        $modelConfig = $agent->model_config ?? [];

        return new AgentDefinition(
            name: $agent->name,
            instructions: (string) ($agent->instructions ?? ''),
            skills: $agent->skills->pluck('slug')->filter()->values()->all(),
            knowledge: $agent->config['knowledge'] ?? [],
            metadata: [
                'config' => $agent->config ?? [],
            ],
            id: (string) $agent->id,
            slug: $agent->slug,
            description: $agent->description,
            model: $modelConfig['model'] ?? null,
            provider: $modelConfig['provider'] ?? null,
            temperature: isset($modelConfig['temperature']) ? (float) $modelConfig['temperature'] : null,
            maxTokens: isset($modelConfig['max_tokens']) ? (int) $modelConfig['max_tokens'] : null,
            tools: $agent->config['tools'] ?? [],
            permissions: $agent->config['permissions'] ?? [],
            runtime: $agent->config['runtime'] ?? [],
            status: $agent->status instanceof Status ? $agent->status->value : (string) $agent->status,
        );
    }
}
