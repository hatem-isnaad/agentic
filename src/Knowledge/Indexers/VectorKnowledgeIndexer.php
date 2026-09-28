<?php

namespace Agentic\Knowledge\Indexers;

use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\Indexer;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\Documents\DocumentCollector;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Knowledge\Support\EmbeddingVectorValidator;
use Agentic\Knowledge\Support\KnowledgeNamespace;

/**
 * Chunks documents, embeds them, and upserts into the configured vector store.
 */
final class VectorKnowledgeIndexer implements Indexer
{
    public function __construct(
        private EmbeddingProvider $embeddings,
        private VectorStore $store,
        private DocumentCollector $documents = new DocumentCollector,
    ) {}

    public function supports(KnowledgeSourceDefinition $source): bool
    {
        return $source->driver === 'vector';
    }

    public function index(KnowledgeSourceDefinition $source): void
    {
        $namespace = KnowledgeNamespace::forSource($source);

        $this->store->deleteNamespace($namespace);

        $contents = array_values($this->documents->collect($source));

        if ($contents === []) {
            return;
        }

        $vectors = $this->embeddings->embedMany($contents);
        $expectedDimensions = $this->expectedDimensions();

        foreach ($contents as $index => $content) {
            $vector = $vectors[$index] ?? [];
            $this->assertEmbeddingVector($vector, $expectedDimensions);

            $id = $namespace.':chunk:'.$index;

            $this->store->upsert(
                $id,
                $vector,
                new KnowledgeChunk($content, $source->slug, metadata: [
                    'chunk' => $index,
                ]),
                [
                    'namespace' => $namespace,
                    'chunk' => $index,
                ],
            );
        }
    }

    private function expectedDimensions(): ?int
    {
        return match (config('agentic.knowledge.vector_store')) {
            'postgres' => (int) config('agentic.knowledge.pgvector.dimensions', 1536),
            'pinecone' => (int) config('agentic.knowledge.pinecone.dimensions', 1536),
            default => null,
        };
    }

    /**
     * @param  list<float|int>  $vector
     */
    private function assertEmbeddingVector(array $vector, ?int $expectedDimensions): void
    {
        if (config('agentic.knowledge.embedding', 'null') === 'null') {
            return;
        }

        EmbeddingVectorValidator::assertUsable($vector, $expectedDimensions);
    }
}
