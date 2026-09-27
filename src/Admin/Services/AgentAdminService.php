<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\AgentData;
use Agentic\Contracts\Repositories\AgentRepository;

final class AgentAdminService
{
    public function __construct(
        private AgentRepository $agents,
    ) {}

    /**
     * @return list<AgentData>
     */
    public function list(): array
    {
        return array_map(
            fn ($agent) => AgentData::fromDefinition($agent),
            $this->agents->all(),
        );
    }

    public function find(string $slug): ?AgentData
    {
        $agent = $this->agents->findBySlug($slug);

        return $agent ? AgentData::fromDefinition($agent) : null;
    }

    public function store(AgentData $data): AgentData
    {
        $saved = $this->agents->save($data->toRepositoryAttributes());

        return AgentData::fromDefinition($saved);
    }

    public function update(string $slug, AgentData $data): AgentData
    {
        $attributes = $data->toRepositoryAttributes();
        $attributes['slug'] = $slug;

        $saved = $this->agents->save($attributes);

        return AgentData::fromDefinition($saved);
    }

    public function delete(string $slug): bool
    {
        return $this->agents->delete($slug);
    }
}
