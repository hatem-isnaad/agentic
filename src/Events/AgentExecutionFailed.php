<?php

namespace Agentic\Events;

final class AgentExecutionFailed
{
    public function __construct(
        public string $executionId,
        public string $agent,
        public string $error,
    ) {}
}
