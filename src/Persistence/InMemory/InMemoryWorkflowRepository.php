<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Workflow\WorkflowDefinition;

final class InMemoryWorkflowRepository implements WorkflowRepository
{
    /** @var array<string, WorkflowDefinition> */
    private array $workflows = [];

    public function seed(WorkflowDefinition ...$workflows): void
    {
        foreach ($workflows as $workflow) {
            $this->workflows[$workflow->slug] = $workflow;
        }
    }

    public function findBySlug(string $slug): ?WorkflowDefinition
    {
        return $this->workflows[$slug] ?? null;
    }

    public function allPublished(): array
    {
        return array_values(array_filter(
            $this->workflows,
            fn (WorkflowDefinition $workflow) => ($workflow->status ?? 'published') === 'published',
        ));
    }

    public function all(): array
    {
        return array_values($this->workflows);
    }

    public function save(array $attributes): WorkflowDefinition
    {
        $definition = new WorkflowDefinition(
            slug: $attributes['slug'],
            name: $attributes['name'],
            steps: is_array($attributes['steps'] ?? null)
                ? $attributes['steps']
                : (is_array($attributes['definition']['steps'] ?? null) ? $attributes['definition']['steps'] : []),
            description: $attributes['description'] ?? null,
            status: $attributes['status'] ?? 'draft',
        );

        $this->workflows[$definition->slug] = $definition;

        return $definition;
    }

    public function delete(string $slug): bool
    {
        if (! isset($this->workflows[$slug])) {
            return false;
        }

        unset($this->workflows[$slug]);

        return true;
    }
}
