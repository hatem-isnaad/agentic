<?php

namespace Agentic\Skill;

use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Exceptions\SkillNotFoundException;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolFactory;

final class SkillResolver
{
    public function __construct(
        private SkillRegistry $registry,
        private ToolRegistry $tools,
        private ?SkillRepository $repository = null,
        private ?ToolFactory $toolFactory = null,
    ) {}

    public function resolve(string $name): SkillDefinition
    {
        if ($this->registry->has($name)) {
            $skill = $this->registry->get($name);
            $this->hydrateTools($skill);

            return $skill;
        }

        if ($this->repository !== null) {
            $skill = $this->repository->findBySlug($name);

            if ($skill !== null) {
                $this->registry->register($skill);
                $this->hydrateTools($skill);

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
     * @param  list<string>  $skillNames
     * @return list<string>
     */
    public function composeTools(array $skillNames): array
    {
        $tools = [];

        foreach ($this->resolveMany($skillNames) as $skill) {
            foreach ($skill->tools as $tool) {
                if ($this->toolFactory?->ensureRegistered($tool) ?? $this->tools->has($tool)) {
                    $tools[] = $tool;
                }
            }
        }

        return array_values(array_unique($tools));
    }

    public function activate(SkillDefinition $skill): void
    {
        if (! $this->registry->has($skill->name)) {
            $this->registry->register($skill);
        }

        $this->hydrateTools($skill);
    }

    public function isActive(SkillDefinition $skill): bool
    {
        $status = $skill->metadata['status'] ?? 'published';

        if ($status === 'archived' || $status === 'draft') {
            return false;
        }

        return ($skill->metadata['active'] ?? true) !== false;
    }

    private function hydrateTools(SkillDefinition $skill): void
    {
        if ($this->toolFactory === null) {
            return;
        }

        foreach ($skill->tools as $tool) {
            $this->toolFactory->ensureRegistered($tool);
        }
    }
}
