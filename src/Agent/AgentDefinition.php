<?php

namespace Agentic\Agent;

final readonly class AgentDefinition
{
    public function __construct(
        public string $name,
        public string $instructions = '',
        public array $skills = [],
        public array $knowledge = [],
        public array $metadata = [],
    ) {}
}
