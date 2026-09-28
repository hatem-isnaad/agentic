<?php

namespace Agentic\Knowledge\Retrievers;

use Agentic\Context\RuntimeContext;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\Retriever;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Knowledge\Support\KnowledgeNamespace;
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
        unset($runtime);

        $namespace = KnowledgeNamespace::forSource($source);
        $vector = $this->embeddings->embed($query);
        $this->logQueryEmbedding($query, $vector, $source->slug, $namespace);

        return $this->store->search($vector, $limit, $namespace);
    }

    /**
     * @param  list<float>  $vector
     */
    private function logQueryEmbedding(string $query, array $vector, string $sourceSlug, string $namespace): void
    {
        $logQueries = (bool) config('agentic.knowledge.log_queries', false);
        $logEmbeddings = (bool) config('agentic.knowledge.log_embeddings', false);

        if (! $logQueries && ! $logEmbeddings) {
            return;
        }

        $preview = (int) config('agentic.knowledge.log_embedding_preview_dims', 8);
        $preview = min($preview, count($vector));

        $payload = [
            'source' => $sourceSlug,
            'namespace' => $namespace,
            'dimensions' => count($vector),
        ];

        if ($logQueries) {
            $payload['query'] = $query;
        }

        if ($logEmbeddings) {
            $payload['vector_preview'] = $preview > 0 ? array_slice($vector, 0, $preview) : [];
            $payload['vector_l2_norm'] = $this->vectorL2Norm($vector);
        }

        Log::info('Agentic RAG: query embedding for vector search', $payload);
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
}
