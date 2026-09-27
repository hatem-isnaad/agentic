<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Skill\SkillDefinition;

interface SkillRepository
{
    public function findById(int|string $id): ?SkillDefinition;

    public function findBySlug(string $slug): ?SkillDefinition;

    /**
     * @return list<SkillDefinition>
     */
    public function allPublished(): array;

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     description?: string|null,
     *     instructions?: string|null,
     *     status?: string,
     *     tools?: list<string>,
     *     knowledge?: list<mixed>,
     *     config?: array<string, mixed>
     * }  $attributes
     */
    public function save(array $attributes): SkillDefinition;

    public function delete(string $slug): bool;
}
