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

    /**
     * @return list<AgentDefinition>
     */
    public function all(): array;

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     description?: string|null,
     *     instructions?: string|null,
     *     status?: string,
     *     provider?: string|null,
     *     model?: string|null,
     *     temperature?: float|null,
     *     max_tokens?: int|null,
     *     skills?: list<string>,
     *     tools?: list<string>,
     *     knowledge?: list<mixed>,
     *     permissions?: list<string>,
     *     runtime?: array<string, mixed>,
     *     config?: array<string, mixed>
     * }  $attributes
     */
    public function save(array $attributes): AgentDefinition;

    public function delete(string $slug): bool;
}
