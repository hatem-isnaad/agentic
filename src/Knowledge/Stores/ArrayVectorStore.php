<?php

namespace Agentic\Knowledge\Stores;

use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeChunk;

/**
 * In-memory vector store for tests and local development.
 */
final class ArrayVectorStore implements VectorStore
{
    /** @var array<string, array{vector: list<float>, chunk: KnowledgeChunk, metadata: array<string, mixed>}> */
    private array $entries = [];

    public function upsert(string $id, array $vector, KnowledgeChunk $chunk, array $metadata = []): void
    {
        $this->entries[$id] = [
            'vector' => $vector,
            'chunk' => $chunk,
            'metadata' => $metadata,
        ];
    }

    public function search(array $vector, int $limit = 5, ?string $namespace = null): array
    {
        unset($namespace);

        return array_slice(
            array_map(fn (array $entry) => $entry['chunk'], $this->entries),
            0,
            $limit,
        );
    }
}
