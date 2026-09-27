<?php

namespace Agentic\Execution;

final readonly class AgentExecutionContext
{
    public function __construct(
        public string $message,
        public array $metadata = [],
        public array $variables = [],
    ) {}
}
