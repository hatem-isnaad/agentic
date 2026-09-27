<?php

namespace Agentic\Skill\Routing;

/**
 * Subset of skill names to activate for a single agent run.
 */
final readonly class SkillRoutingResult
{
    /**
     * @param  list<string>  $skills
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $skills,
        public string $strategy,
        public float $confidence = 1.0,
        public array $metadata = [],
    ) {}
}
