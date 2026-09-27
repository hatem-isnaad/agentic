<?php

namespace Agentic\Knowledge\Contracts;

use Agentic\Context\RuntimeContext;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeSourceDefinition;

/**
 * Retrieves relevant knowledge chunks for a query.
 */
interface Retriever
{
    public function supports(KnowledgeSourceDefinition $source): bool;

    /**
     * @return list<KnowledgeChunk>
     */
    public function retrieve(
        KnowledgeSourceDefinition $source,
        string $query,
        int $limit = 5,
        ?RuntimeContext $runtime = null,
    ): array;
}
