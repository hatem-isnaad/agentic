<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Workflow\WorkflowDefinition;

interface WorkflowRepository
{
    public function findBySlug(string $slug): ?WorkflowDefinition;

    /**
     * @return list<WorkflowDefinition>
     */
    public function allPublished(): array;

    /**
     * @return list<WorkflowDefinition>
     */
    public function all(): array;

    public function save(array $attributes): WorkflowDefinition;

    public function delete(string $slug): bool;
}
