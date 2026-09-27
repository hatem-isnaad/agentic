<?php

namespace Agentic\Exceptions;

final class AgentExecutionFailedException extends AgenticException
{
    public function __construct(string $agent, string $detail = '', ?\Throwable $previous = null)
    {
        $suffix = $detail !== '' ? ": {$detail}" : '.';

        parent::__construct("Agent [{$agent}] execution failed{$suffix}", 0, $previous);
    }
}
