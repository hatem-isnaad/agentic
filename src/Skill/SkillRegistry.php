<?php

namespace Agentic\Skill;

use InvalidArgumentException;

final class SkillRegistry
{
    /** @var array<string, SkillDefinition> */
    private array $skills = [];

    public function register(SkillDefinition $skill): void
    {
        if ($skill->name === '') {
            throw new InvalidArgumentException('A skill name is required.');
        }

        $this->skills[$skill->name] = $skill;
    }

    public function has(string $name): bool
    {
        return isset($this->skills[$name]);
    }

    public function get(string $name): SkillDefinition
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException("Skill [{$name}] is not registered.");
        }

        return $this->skills[$name];
    }

    /** @return array<string, SkillDefinition> */
    public function all(): array
    {
        return $this->skills;
    }
}
