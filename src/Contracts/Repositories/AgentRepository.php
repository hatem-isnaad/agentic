<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Agent\AgentDefinition;

/**
 * Persistence boundary for Agents.
 *
 * Runtime depends on this contract — never on Eloquent models.
 */
interface AgentRepository
{
    public function findById(int|string $id): ?AgentDefinition;

    public function findBySlug(string $slug): ?AgentDefinition;

    /**
     * @return list<AgentDefinition>
     */
    public function allPublished(): array;
}
