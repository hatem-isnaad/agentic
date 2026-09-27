<?php

namespace Agentic\Knowledge\Retrievers;

use Agentic\Context\RuntimeContext;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\Retriever;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Illuminate\Support\Facades\Log;

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

        $this->logQueryEmbedding($query, $vector, $source->slug, $namespace);

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

    /**
     * @param  list<float>  $vector
     */
    private function logQueryEmbedding(string $query, array $vector, string $sourceSlug, string $namespace): void
    {
        if (! (bool) config('agentic.knowledge.log_embeddings', false)) {
            return;
        }

        $preview = (int) config('agentic.knowledge.log_embedding_preview_dims', 8);
        $preview = min($preview, count($vector));

        Log::info('Agentic RAG: query embedding for vector search', [
            'query' => $query,
            'source' => $sourceSlug,
            'namespace' => $namespace,
            'dimensions' => count($vector),
            'vector_preview' => $preview > 0 ? array_slice($vector, 0, $preview) : [],
            'vector_l2_norm' => $this->vectorL2Norm($vector),
        ]);
    }

    /**
     * @param  list<float>  $vector
     */
    private function vectorL2Norm(array $vector): float
    {
        $sum = 0.0;
        foreach ($vector as $v) {
            $sum += (float) $v * (float) $v;
        }

        return sqrt($sum);
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
