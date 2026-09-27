<?php

namespace Agentic\Exceptions;

final class DriverNotFoundException extends AgenticException
{
    public function __construct(string $driver)
    {
        parent::__construct("Tool driver [{$driver}] is not registered.");
    }
}
