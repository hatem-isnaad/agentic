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
        $matches = [];

        foreach ($this->entries as $entry) {
            $entryNamespace = is_string($entry['metadata']['namespace'] ?? null)
                ? $entry['metadata']['namespace']
                : 'default';

            if ($namespace !== null && $namespace !== '' && $entryNamespace !== $namespace) {
                continue;
            }

            $matches[] = $entry['chunk'];
        }

        return array_slice($matches, 0, $limit);
    }

    public function deleteNamespace(string $namespace): void
    {
        foreach ($this->entries as $id => $entry) {
            $entryNamespace = is_string($entry['metadata']['namespace'] ?? null)
                ? $entry['metadata']['namespace']
                : 'default';

            if ($entryNamespace === $namespace) {
                unset($this->entries[$id]);
            }
        }
    }
}
