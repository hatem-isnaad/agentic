<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Enums\Status;
use Agentic\Models\Skill;
use Agentic\Models\Tool;
use Agentic\Skill\SkillDefinition;

final class EloquentSkillRepository implements SkillRepository
{
    public function findById(int|string $id): ?SkillDefinition
    {
        $skill = Skill::query()->with(['tools' => fn ($q) => $q->where('status', Status::Published)])->find($id);

        return $skill ? $this->toDefinition($skill) : null;
    }

    public function findBySlug(string $slug): ?SkillDefinition
    {
        $skill = Skill::query()
            ->with(['tools' => fn ($q) => $q->where('status', Status::Published)])
            ->where('slug', $slug)
            ->first();

        return $skill ? $this->toDefinition($skill) : null;
    }

    public function allPublished(): array
    {
        return Skill::query()
            ->where('status', Status::Published)
            ->with(['tools' => fn ($q) => $q->where('status', Status::Published)])
            ->get()
            ->map(fn (Skill $skill) => $this->toDefinition($skill))
            ->all();
    }

    public function all(): array
    {
        return Skill::query()
            ->with(['tools'])
            ->orderBy('name')
            ->get()
            ->map(fn (Skill $skill) => $this->toDefinition($skill))
            ->all();
    }

    public function save(array $attributes): SkillDefinition
    {
        $config = $attributes['config'] ?? [];

        if (isset($attributes['knowledge'])) {
            $config['knowledge'] = $attributes['knowledge'];
        }

        $model = Skill::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'instructions' => $attributes['instructions'] ?? null,
                'config' => $config,
                'status' => $attributes['status'] ?? Status::Draft->value,
            ],
        );

        if (array_key_exists('tools', $attributes)) {
            $toolIds = Tool::query()
                ->whereIn('slug', $attributes['tools'] ?? [])
                ->pluck('id', 'slug');

            $sync = [];
            foreach ($attributes['tools'] ?? [] as $position => $slug) {
                if ($toolIds->has($slug)) {
                    $sync[$toolIds[$slug]] = ['position' => $position];
                }
            }

            $model->tools()->sync($sync);
        }

        return $this->toDefinition($model->fresh(['tools']));
    }

    public function delete(string $slug): bool
    {
        return Skill::query()->where('slug', $slug)->delete() > 0;
    }

    private function toDefinition(Skill $skill): SkillDefinition
    {
        return new SkillDefinition(
            name: $skill->slug,
            description: (string) ($skill->description ?? ''),
            tools: $skill->tools->pluck('slug')->filter()->values()->all(),
            knowledge: $skill->config['knowledge'] ?? [],
            metadata: [
                'id' => $skill->id,
                'display_name' => $skill->name,
                'instructions' => $skill->instructions,
                'status' => $skill->status instanceof Status ? $skill->status->value : (string) $skill->status,
            ],
        );
    }
}
