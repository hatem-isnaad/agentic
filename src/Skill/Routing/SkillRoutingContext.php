<?php

namespace Agentic\Skill\Routing;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\RuntimeContext;

/**
 * Input for selecting which agent skills (and their tools) are exposed to the LLM.
 */
final readonly class SkillRoutingContext
{
    /**
     * @param  list<string>  $candidateSkills  Skill names configured on the agent
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public AgentDefinition $agent,
        public string $message,
        public array $candidateSkills,
        public ?RuntimeContext $runtime = null,
        public ?string $skillHint = null,
        public array $attributes = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
