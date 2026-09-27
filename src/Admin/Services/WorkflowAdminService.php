<?php

namespace Agentic\Admin\Services;

use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Workflow\WorkflowDefinition;

final class WorkflowAdminService
{
    public function __construct(
        private WorkflowRepository $workflows,
    ) {}

    /**
     * @return list<WorkflowDefinition>
     */
    public function list(): array
    {
        return $this->workflows->all();
    }

    public function find(string $slug): ?WorkflowDefinition
    {
        return $this->workflows->findBySlug($slug);
    }

    public function store(array $attributes): WorkflowDefinition
    {
        return $this->workflows->save($attributes);
    }

    public function update(string $slug, array $attributes): WorkflowDefinition
    {
        $attributes['slug'] = $slug;

        return $this->workflows->save($attributes);
    }

    public function delete(string $slug): bool
    {
        return $this->workflows->delete($slug);
    }
}
