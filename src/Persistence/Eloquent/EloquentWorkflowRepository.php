<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\WorkflowRepository;
use Agentic\Enums\Status;
use Agentic\Models\Workflow;
use Agentic\Workflow\WorkflowDefinition;

final class EloquentWorkflowRepository implements WorkflowRepository
{
    public function findBySlug(string $slug): ?WorkflowDefinition
    {
        $workflow = Workflow::query()->where('slug', $slug)->first();

        return $workflow ? $this->toDefinition($workflow) : null;
    }

    public function allPublished(): array
    {
        return Workflow::query()
            ->where('status', Status::Published)
            ->orderBy('name')
            ->get()
            ->map(fn (Workflow $workflow) => $this->toDefinition($workflow))
            ->all();
    }

    public function all(): array
    {
        return Workflow::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Workflow $workflow) => $this->toDefinition($workflow))
            ->all();
    }

    public function save(array $attributes): WorkflowDefinition
    {
        $definition = is_array($attributes['definition'] ?? null)
            ? $attributes['definition']
            : ['steps' => $attributes['steps'] ?? []];

        $model = Workflow::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'definition' => $definition,
                'status' => $attributes['status'] ?? Status::Draft->value,
            ],
        );

        return $this->toDefinition($model);
    }

    public function delete(string $slug): bool
    {
        return Workflow::query()->where('slug', $slug)->delete() > 0;
    }

    private function toDefinition(Workflow $workflow): WorkflowDefinition
    {
        $definition = is_array($workflow->definition) ? $workflow->definition : [];
        $steps = is_array($definition['steps'] ?? null) ? $definition['steps'] : [];

        return new WorkflowDefinition(
            slug: $workflow->slug,
            name: $workflow->name,
            steps: $steps,
            description: $workflow->description,
            status: $workflow->status instanceof Status ? $workflow->status->value : (string) $workflow->status,
        );
    }
}
