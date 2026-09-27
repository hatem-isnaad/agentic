<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Execution\Execution;

interface ExecutionRepository
{
    public function store(Execution $execution): Execution;

    public function find(string $id): ?Execution;

    public function update(Execution $execution): Execution;
}
