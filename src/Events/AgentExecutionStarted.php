<?php

namespace Agentic\Events;

final class AgentExecutionStarted
{
    public function __construct(
        public string $executionId,
        public string $agent,
        public string $message,
    ) {}
}
