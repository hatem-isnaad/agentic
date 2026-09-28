<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Models\Skill;

final class EloquentAgentRepository implements AgentRepository
{
    public function findById(int|string $id): ?AgentDefinition
    {
        $agent = Agent::query()->with($this->skillEagerLoad())->find($id);

        return $agent ? $this->toDefinition($agent) : null;
    }

    public function findBySlug(string $slug): ?AgentDefinition
    {
        $agent = Agent::query()
            ->with($this->skillEagerLoad())
            ->where('slug', $slug)
            ->first();

        return $agent ? $this->toDefinition($agent) : null;
    }

    public function allPublished(): array
    {
        return Agent::query()
            ->published()
            ->with($this->skillEagerLoad())
            ->get()
            ->map(fn (Agent $agent) => $this->toDefinition($agent))
            ->all();
    }

    public function all(): array
    {
        return Agent::query()
            ->with($this->skillEagerLoad(false))
            ->orderBy('name')
            ->get()
            ->map(fn (Agent $agent) => $this->toDefinition($agent))
            ->all();
    }

    public function save(array $attributes): AgentDefinition
    {
        $config = $attributes['config'] ?? [];

        foreach (['tools', 'knowledge', 'permissions', 'runtime'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $config[$key] = $attributes[$key];
            }
        }

        $modelConfig = array_filter([
            'provider' => $attributes['provider'] ?? null,
            'model' => $attributes['model'] ?? null,
            'temperature' => $attributes['temperature'] ?? null,
            'max_tokens' => $attributes['max_tokens'] ?? null,
        ], fn ($value) => $value !== null);

        $model = Agent::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'instructions' => $attributes['instructions'] ?? null,
                'model_config' => $modelConfig === [] ? null : $modelConfig,
                'config' => $config === [] ? null : $config,
                'status' => $attributes['status'] ?? Status::Draft->value,
            ],
        );

        if (array_key_exists('skills', $attributes)) {
            $skillIds = Skill::query()
                ->whereIn('slug', $attributes['skills'] ?? [])
                ->pluck('id', 'slug');

            $sync = [];
            foreach ($attributes['skills'] ?? [] as $position => $slug) {
                if ($skillIds->has($slug)) {
                    $sync[$skillIds[$slug]] = ['position' => $position];
                }
            }

            $model->skills()->sync($sync);
        }

        return $this->toDefinition($model->fresh($this->skillEagerLoad(false)));
    }

    public function delete(string $slug): bool
    {
        return Agent::query()->where('slug', $slug)->delete() > 0;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function skillEagerLoad(bool $publishedOnly = true): array
    {
        $tools = $publishedOnly
            ? fn ($query) => $query->where('status', Status::Published)
            : fn ($query) => $query;

        return [
            'skills' => $publishedOnly
                ? fn ($query) => $query->where('status', Status::Published)->with(['tools' => $tools])
                : fn ($query) => $query->with(['tools' => $tools]),
        ];
    }

    private function toDefinition(Agent $agent): AgentDefinition
    {
        $modelConfig = $agent->model_config ?? [];
        $directTools = array_values(array_filter(
            is_array($agent->config['tools'] ?? null) ? $agent->config['tools'] : [],
            'is_string',
        ));
        $skillTools = $agent->skills
            ->flatMap(fn (Skill $skill) => $skill->tools->pluck('slug'))
            ->filter()
            ->values()
            ->all();
        $configuredPermissions = array_values(array_filter(
            is_array($agent->config['permissions'] ?? null) ? $agent->config['permissions'] : [],
            'is_string',
        ));

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
            tools: $directTools,
            permissions: array_values(array_unique(array_merge($configuredPermissions, $directTools, $skillTools))),
            runtime: $agent->config['runtime'] ?? [],
            status: $agent->status instanceof Status ? $agent->status->value : (string) $agent->status,
        );
    }
}
