<?php

namespace Agentic\Routing\Support;

use Agentic\Contracts\Repositories\AgentRepository;

/**
 * Builds agent catalog entries for optional LLM routing from persisted agents.
 */
final class AgentCatalogBuilder
{
    public function __construct(
        private ?AgentRepository $agents = null,
    ) {}

    /**
     * @return list<array{slug: string, label: string, description?: string}>
     */
    public function published(): array
    {
        $configured = config('agentic.routing.llm.catalog', []);

        if (is_array($configured) && $configured !== []) {
            return $configured;
        }

        if ($this->agents === null || ! config('agentic.routing.llm.use_agent_repository', true)) {
            return [];
        }

        $catalog = [];

        foreach ($this->agents->allPublished() as $agent) {
            $slug = $agent->slug ?? $agent->name;

            if (! is_string($slug) || $slug === '') {
                continue;
            }

            $catalog[] = [
                'slug' => $slug,
                'label' => $agent->name,
                'description' => $agent->description ?? $agent->instructions ?? '',
            ];
        }

        return $catalog;
    }
}
