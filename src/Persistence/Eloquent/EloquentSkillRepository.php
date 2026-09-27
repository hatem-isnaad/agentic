<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Enums\Status;
use Agentic\Models\Skill;
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
