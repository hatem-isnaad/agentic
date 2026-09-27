<?php

namespace Agentic\Workflow;

/**
 * Resume a workflow from a saved step pointer and variable snapshot.
 */
final readonly class WorkflowContinuation
{
    /**
     * @param  array<string, mixed>  $variables
     * @param  list<array<string, mixed>>  $trace
     */
    public function __construct(
        public int $pointer,
        public array $variables,
        public array $trace = [],
    ) {}
}
