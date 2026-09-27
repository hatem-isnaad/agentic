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
        $vector = $this->embeddings->embed($query);
        $namespace = is_string($source->configuration['namespace'] ?? null)
            ? $source->configuration['namespace']
            : $source->slug;

        $chunks = $this->store->search($vector, $limit, $namespace);
        $tenant = $this->tenantKey($runtime);

        if ($tenant === null) {
            return $chunks;
        }

        return array_values(array_filter(
            $chunks,
            fn (KnowledgeChunk $chunk): bool => $this->chunkVisibleForTenant($chunk, $tenant),
        ));
    }

    private function tenantKey(?RuntimeContext $runtime): ?string
    {
        if ($runtime === null) {
            return null;
        }

        $tenant = $runtime->tenant();

        if (is_string($tenant) && $tenant !== '') {
            return $tenant;
        }

        if (is_int($tenant)) {
            return (string) $tenant;
        }

        if (is_object($tenant) && isset($tenant->id)) {
            return (string) $tenant->id;
        }

        $tenantId = $runtime->get('tenant_id');

        return is_string($tenantId) || is_int($tenantId) ? (string) $tenantId : null;
    }

    private function chunkVisibleForTenant(KnowledgeChunk $chunk, string $tenant): bool
    {
        $chunkTenant = $chunk->metadata['tenant'] ?? null;

        if ($chunkTenant === null || $chunkTenant === '') {
            return true;
        }

        return (string) $chunkTenant === $tenant;
    }
}
