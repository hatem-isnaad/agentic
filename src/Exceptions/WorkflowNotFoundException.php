<?php

namespace Agentic\Exceptions;

use RuntimeException;

final class WorkflowNotFoundException extends RuntimeException
{
    public function __construct(string $slug)
    {
        parent::__construct("Workflow [{$slug}] was not found.");
    }
}
