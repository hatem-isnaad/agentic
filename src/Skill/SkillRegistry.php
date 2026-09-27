<?php

namespace Agentic\Skill;

use Agentic\Exceptions\SkillNotFoundException;
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

    public function unregister(string $name): void
    {
        unset($this->skills[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->skills[$name]);
    }

    public function find(string $name): ?SkillDefinition
    {
        return $this->skills[$name] ?? null;
    }

    public function resolve(string $name): SkillDefinition
    {
        return $this->get($name);
    }

    public function get(string $name): SkillDefinition
    {
        if (! $this->has($name)) {
            throw new SkillNotFoundException($name);
        }

        return $this->skills[$name];
    }

    /** @return array<string, SkillDefinition> */
    public function all(): array
    {
        return $this->skills;
    }
}
