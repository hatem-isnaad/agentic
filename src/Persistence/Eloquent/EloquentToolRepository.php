<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Enums\Status;
use Agentic\Models\Tool;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Tool\ToolDefinition;

final class EloquentToolRepository implements ToolRepository
{
    public function __construct(
        private ToolVersionPublisher $publisher,
    ) {}

    public function findById(int|string $id): ?ToolDefinition
    {
        $tool = Tool::query()->find($id);

        return $tool ? $this->toDefinition($tool) : null;
    }

    public function findBySlug(string $slug): ?ToolDefinition
    {
        $tool = Tool::query()->where('slug', $slug)->first();

        return $tool ? $this->toDefinition($tool) : null;
    }

    public function allPublished(): array
    {
        return Tool::query()
            ->where('status', Status::Published)
            ->get()
            ->map(fn (Tool $tool) => $this->toDefinition($tool))
            ->all();
    }

    public function save(array $attributes): ToolDefinition
    {
        $driver = $attributes['driver'];

        $model = Tool::query()->updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'type' => $driver,
                'driver' => $driver,
                'config' => $attributes['config'] ?? [],
                'status' => $attributes['status'] ?? Status::Draft->value,
            ],
        );

        $definition = $attributes['definition'] ?? null;

        if (is_array($definition) && ($attributes['publish'] ?? false)) {
            $this->publisher->publish($model, $definition);
            $model = $model->fresh();
        }

        return $this->toDefinition($model);
    }

    public function delete(string $slug): bool
    {
        return Tool::query()->where('slug', $slug)->delete() > 0;
    }

    private function toDefinition(Tool $tool): ToolDefinition
    {
        $version = $tool->latestPublishedVersion()->first();
        $definition = $version?->definition ?? [];

        return new ToolDefinition(
            name: $tool->slug,
            description: (string) ($tool->description ?? $tool->name),
            inputSchema: $definition['input_schema'] ?? $definition['inputSchema'] ?? [],
            outputSchema: $definition['output_schema'] ?? $definition['outputSchema'] ?? [],
            driver: $tool->driver,
            configuration: array_merge($tool->config ?? [], $definition),
            permissions: $definition['permissions'] ?? [],
            status: $tool->status instanceof Status ? $tool->status->value : (string) $tool->status,
            runtime: $definition['runtime'] ?? [],
            version: $version?->version,
            connection: is_string($definition['connection'] ?? null) ? $definition['connection'] : null,
            id: $tool->getKey(),
        );
    }
}
