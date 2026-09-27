<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Tool\ToolDefinition;

interface ToolRepository
{
    public function findById(int|string $id): ?ToolDefinition;

    public function findBySlug(string $slug): ?ToolDefinition;

    /**
     * @return list<ToolDefinition>
     */
    public function allPublished(): array;

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     driver: string,
     *     description?: string|null,
     *     status?: string,
     *     config?: array<string, mixed>,
     *     definition?: array<string, mixed>,
     *     publish?: bool
     * }  $attributes
     */
    public function save(array $attributes): ToolDefinition;

    public function delete(string $slug): bool;
}
