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

    /**
     * @return list<KnowledgeSourceDefinition>
     */
    public function all(): array;

    public function save(KnowledgeSourceDefinition $source): KnowledgeSourceDefinition;

    public function delete(string $slug): bool;
}
