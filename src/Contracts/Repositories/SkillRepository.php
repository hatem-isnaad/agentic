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
}
