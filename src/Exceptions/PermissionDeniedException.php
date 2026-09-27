<?php

namespace Agentic\Exceptions;

final class PermissionDeniedException extends AgenticException
{
    public function __construct(string $ability, string $message = '')
    {
        parent::__construct($message !== '' ? $message : "Permission denied for [{$ability}].");
    }
}
