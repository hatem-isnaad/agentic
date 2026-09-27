<?php

namespace Agentic\Exceptions;

final class InvalidToolInputException extends AgenticException
{
    public function __construct(string $tool, string $detail = '')
    {
        $suffix = $detail !== '' ? ": {$detail}" : '.';

        parent::__construct("Invalid input for tool [{$tool}]{$suffix}");
    }
}
