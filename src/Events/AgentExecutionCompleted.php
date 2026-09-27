<?php

namespace Agentic\Events;

final class AgentExecutionCompleted
{
    public function __construct(
        public string $executionId,
        public string $agent,
        public mixed $output = null,
    ) {}
}
