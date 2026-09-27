<?php

namespace Agentic\Knowledge\Indexers;

use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Contracts\Indexer;
use Agentic\Knowledge\Contracts\VectorStore;
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
    ) {}

    public function supports(KnowledgeSourceDefinition $source): bool
    {
        return $source->driver === 'vector';
    }

    public function index(KnowledgeSourceDefinition $source): void
    {
        $documents = $source->configuration['documents'] ?? [];
        $namespace = is_string($source->configuration['namespace'] ?? null)
            ? $source->configuration['namespace']
            : $source->slug;

        if (! is_array($documents)) {
            return;
        }

        foreach ($documents as $index => $document) {
            $content = is_string($document)
                ? $document
                : (string) (is_array($document) ? ($document['content'] ?? '') : '');

            if ($content === '') {
                continue;
            }

            $id = $source->slug.':'.$index;
            $vector = $this->embeddings->embed($content);

            $this->store->upsert(
                $id,
                $vector,
                new KnowledgeChunk($content, $source->slug),
                ['namespace' => $namespace, 'index' => $index],
            );
        }
    }
}
