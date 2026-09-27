<?php

namespace Agentic\Skill;

use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Exceptions\SkillNotFoundException;
use Agentic\Tool\Registry\ToolRegistry;

/**
 * Resolves Skills and composes their tools for Agent activation.
 */
final class SkillResolver
{
    public function __construct(
        private SkillRegistry $registry,
        private ToolRegistry $tools,
        private ?SkillRepository $repository = null,
    ) {}

    public function resolve(string $name): SkillDefinition
    {
        if ($this->registry->has($name)) {
            return $this->registry->get($name);
        }

        if ($this->repository !== null) {
            $skill = $this->repository->findBySlug($name);

            if ($skill !== null) {
                $this->registry->register($skill);

                return $skill;
            }
        }

        throw new SkillNotFoundException($name);
    }

    /**
     * @param  list<string>  $skillNames
     * @return list<SkillDefinition>
     */
    public function resolveMany(array $skillNames): array
    {
        $skills = [];

        foreach ($skillNames as $name) {
            try {
                $skill = $this->resolve($name);
            } catch (SkillNotFoundException) {
                continue;
            }

            if ($this->isActive($skill)) {
                $skills[] = $skill;
            }
        }

        return $skills;
    }

    /**
     * Compose the unique active tool names contributed by the given skills.
     *
     * @param  list<string>  $skillNames
     * @return list<string>
     */
    public function composeTools(array $skillNames): array
    {
        $tools = [];

        foreach ($this->resolveMany($skillNames) as $skill) {
            foreach ($skill->tools as $tool) {
                if ($this->tools->has($tool)) {
                    $tools[] = $tool;
                }
            }
        }

        return array_values(array_unique($tools));
    }

    /**
     * Activate a skill into the registry (idempotent).
     */
    public function activate(SkillDefinition $skill): void
    {
        if (! $this->registry->has($skill->name)) {
            $this->registry->register($skill);
        }
    }

    public function isActive(SkillDefinition $skill): bool
    {
        $status = $skill->metadata['status'] ?? 'published';

        if ($status === 'archived' || $status === 'draft') {
            return false;
        }

        return ($skill->metadata['active'] ?? true) !== false;
    }
}
