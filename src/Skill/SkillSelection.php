<?php

namespace Agentic\Skill;

/**
 * Selected skills for a single request.
 */
final readonly class SkillSelection
{
    /**
     * @param  list<string>  $skills
     * @param  array<string, list<string>>  $matches
     */
    public function __construct(
        public array $skills,
        public array $matches = [],
    ) {}
}
