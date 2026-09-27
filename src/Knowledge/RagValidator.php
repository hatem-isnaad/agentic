<?php

namespace Agentic\Knowledge;

use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\Indexers\VectorKnowledgeIndexer;
use Agentic\Knowledge\Providers\DeterministicEmbeddingProvider;
use Agentic\Knowledge\Retrievers\VectorKnowledgeRetriever;

final class RagValidator
{
    public function __construct(
        private KnowledgeRepository $sources,
        private EmbeddingProvider $embeddings,
        private VectorStore $store,
    ) {}

    /**
     * @return array{success: bool, message: string, top_score: ?float, top_content: ?string}
     */
    public function validate(?EmbeddingProvider $embeddings = null): array
    {
        $embeddings ??= $this->embeddings;

        $slug = '__agentic_rag_self_test__';
        $needle = 'agentic-rag-validate-token-'.bin2hex(random_bytes(4));
        $haystack = "Policy document containing {$needle} for retrieval validation.";

        if ($this->sources->findBySlug($slug) !== null) {
            $this->sources->delete($slug);
        }

        $source = $this->sources->save(new KnowledgeSourceDefinition(
            slug: $slug,
            name: 'RAG self test',
            driver: 'vector',
            configuration: [
                'documents' => [$haystack],
                'chunk_size' => 500,
            ],
            status: 'published',
        ));

        $indexer = new VectorKnowledgeIndexer($embeddings, $this->store);
        $retriever = new VectorKnowledgeRetriever($embeddings, $this->store);

        try {
            $indexer->index($source);

            $chunks = $retriever->retrieve(
                $source,
                "Where is the {$needle} mentioned?",
                3,
            );
        } finally {
            $this->store->deleteNamespace($slug);
            $this->sources->delete($slug);
        }

        if ($chunks === []) {
            return [
                'success' => false,
                'message' => 'RAG validation failed: no chunks returned.',
                'top_score' => null,
                'top_content' => null,
            ];
        }

        $top = $chunks[0];
        $found = str_contains($top->content, $needle);

        return [
            'success' => $found,
            'message' => $found
                ? 'RAG pipeline OK: indexed content retrieved for the validation query.'
                : 'RAG validation failed: top chunk did not contain the indexed token.',
            'top_score' => $top->score,
            'top_content' => $top->content,
        ];
    }

    public function offlineEmbeddings(): EmbeddingProvider
    {
        $dimensions = (int) config('agentic.knowledge.pgvector.dimensions', 1536);

        if (config('agentic.knowledge.vector_store') === 'postgres') {
            return new DeterministicEmbeddingProvider($dimensions);
        }

        return new DeterministicEmbeddingProvider(32);
    }
}
