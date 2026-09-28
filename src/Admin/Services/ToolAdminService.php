<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\ToolData;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Exceptions\ToolNotFoundException;

final class ToolAdminService
{
    public function __construct(
        private ToolRepository $tools,
    ) {}

    /**
     * @return list<ToolData>
     */
    public function list(): array
    {
        return array_map(
            fn ($tool) => ToolData::fromDefinition($tool),
            $this->tools->all(),
        );
    }

    public function find(string $slug): ?ToolData
    {
        $tool = $this->tools->findBySlug($slug);

        return $tool ? ToolData::fromDefinition($tool) : null;
    }

    public function store(ToolData $data): ToolData
    {
        $saved = $this->tools->save($data->toRepositoryAttributes());

        return ToolData::fromDefinition($saved);
    }

    public function update(string $slug, ToolData $data): ToolData
    {
        $attributes = $data->toRepositoryAttributes();
        $attributes['slug'] = $slug;

        $saved = $this->tools->save($attributes);

        return ToolData::fromDefinition($saved);
    }

    public function delete(string $slug): bool
    {
        return $this->tools->delete($slug);
    }

    public function clone(string $slug, ?string $name = null, ?string $newSlug = null): ToolData
    {
        $source = $this->find($slug);

        if ($source === null) {
            throw new ToolNotFoundException($slug);
        }

        $definition = $source->definition;
        $hasDefinition = $definition !== [];
        $label = $name !== null && $name !== '' ? $name : $this->copyLabel($source->name);

        return $this->store(new ToolData(
            name: $label,
            slug: $this->uniqueSlug($newSlug !== null && $newSlug !== '' ? $newSlug : $source->slug.'-copy'),
            driver: $source->driver,
            description: $label,
            status: $hasDefinition ? 'published' : 'draft',
            config: $source->config,
            definition: $definition,
            publish: $hasDefinition,
        ));
    }

    private function copyLabel(string $name): string
    {
        return str_ends_with($name, ' (copy)') ? $name : $name.' (copy)';
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        while ($this->tools->findBySlug($slug) !== null) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
