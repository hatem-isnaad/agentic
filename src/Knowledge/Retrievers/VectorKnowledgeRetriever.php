<?php

namespace Agentic\Knowledge\Retrievers;

use Agentic\Context\RuntimeContext;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\Retriever;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeSourceDefinition;

/**
 * Retrieves knowledge via embeddings + vector store.
 */
final class VectorKnowledgeRetriever implements Retriever
{
    public function __construct(
        private EmbeddingProvider $embeddings,
        private VectorStore $store,
    ) {}

    public function supports(KnowledgeSourceDefinition $source): bool
    {
        return $source->driver === 'vector';
    }

    public function retrieve(
        KnowledgeSourceDefinition $source,
        string $query,
        int $limit = 5,
        ?RuntimeContext $runtime = null,
    ): array {
        unset($runtime);

        $vector = $this->embeddings->embed($query);
        $namespace = is_string($source->configuration['namespace'] ?? null)
            ? $source->configuration['namespace']
            : $source->slug;

        return $this->store->search($vector, $limit, $namespace);
    }
}
