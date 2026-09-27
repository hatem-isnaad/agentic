<?php

namespace Agentic\Exceptions;

final class ToolNotFoundException extends AgenticException
{
    public function __construct(string $identifier)
    {
        parent::__construct("Tool [{$identifier}] was not found.");
    }
}
