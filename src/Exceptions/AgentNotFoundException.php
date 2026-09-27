<?php

namespace Agentic\Exceptions;

final class AgentNotFoundException extends AgenticException
{
    public function __construct(string $identifier)
    {
        parent::__construct("Agent [{$identifier}] was not found.");
    }
}
