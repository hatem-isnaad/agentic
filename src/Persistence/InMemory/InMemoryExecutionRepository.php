<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Execution\Execution;

final class InMemoryExecutionRepository implements ExecutionRepository
{
    /** @var array<string, Execution> */
    private array $executions = [];

    public function store(Execution $execution): Execution
    {
        $this->executions[$execution->id] = $execution;

        return $execution;
    }

    public function find(string $id): ?Execution
    {
        return $this->executions[$id] ?? null;
    }

    public function update(Execution $execution): Execution
    {
        $this->executions[$execution->id] = $execution;

        return $execution;
    }
}
