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
}
