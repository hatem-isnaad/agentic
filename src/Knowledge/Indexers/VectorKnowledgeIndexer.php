<?php

namespace Agentic\Knowledge\Indexers;

use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\Indexer;
use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\Documents\DocumentCollector;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeSourceDefinition;

/**
 * Chunks documents, embeds them, and upserts into the configured vector store.
 */
final class VectorKnowledgeIndexer implements Indexer
{
    public function __construct(
        private EmbeddingProvider $embeddings,
        private VectorStore $store,
        private DocumentCollector $documents = new DocumentCollector(),
    ) {}

    public function supports(KnowledgeSourceDefinition $source): bool
    {
        return $source->driver === 'vector';
    }

    public function index(KnowledgeSourceDefinition $source): void
    {
        $namespace = is_string($source->configuration['namespace'] ?? null)
            ? $source->configuration['namespace']
            : $source->slug;

        $this->store->deleteNamespace($namespace);

        $tenant = is_string($source->configuration['tenant'] ?? null)
            ? $source->configuration['tenant']
            : null;

        foreach ($this->documents->collect($source) as $index => $content) {
            $id = $source->slug.':chunk:'.$index;
            $vector = $this->embeddings->embed($content);

            $this->store->upsert(
                $id,
                $vector,
                new KnowledgeChunk($content, $source->slug, metadata: [
                    'chunk' => $index,
                    'tenant' => $tenant,
                ]),
                [
                    'namespace' => $namespace,
                    'chunk' => $index,
                    'tenant' => $tenant,
                ],
            );
        }
    }
}
