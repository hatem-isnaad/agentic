<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Knowledge\KnowledgeSourceDefinition;

interface KnowledgeRepository
{
    public function findBySlug(string $slug): ?KnowledgeSourceDefinition;

    /**
     * @return list<KnowledgeSourceDefinition>
     */
    public function allPublished(): array;
}
