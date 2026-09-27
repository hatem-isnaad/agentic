<?php

namespace Agentic\Skill;

final readonly class SkillDefinition
{
    public function __construct(
        public string $name,
        public string $description = '',
        public array $tools = [],
        public array $knowledge = [],
        public array $metadata = [],
    ) {}
}
