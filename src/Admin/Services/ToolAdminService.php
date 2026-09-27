<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\ToolData;
use Agentic\Contracts\Repositories\ToolRepository;

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
}
