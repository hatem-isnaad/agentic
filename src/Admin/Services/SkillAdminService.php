<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\SkillData;
use Agentic\Contracts\Repositories\SkillRepository;

final class SkillAdminService
{
    public function __construct(
        private SkillRepository $skills,
    ) {}

    /**
     * @return list<SkillData>
     */
    public function list(): array
    {
        return array_map(
            fn ($skill) => SkillData::fromDefinition($skill),
            $this->skills->all(),
        );
    }

    public function find(string $slug): ?SkillData
    {
        $skill = $this->skills->findBySlug($slug);

        return $skill ? SkillData::fromDefinition($skill) : null;
    }

    public function store(SkillData $data): SkillData
    {
        $saved = $this->skills->save($data->toRepositoryAttributes());

        return SkillData::fromDefinition($saved);
    }

    public function update(string $slug, SkillData $data): SkillData
    {
        $attributes = $data->toRepositoryAttributes();
        $attributes['slug'] = $slug;

        $saved = $this->skills->save($attributes);

        return SkillData::fromDefinition($saved);
    }

    public function delete(string $slug): bool
    {
        return $this->skills->delete($slug);
    }
}
