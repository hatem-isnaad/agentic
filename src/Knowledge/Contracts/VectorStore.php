<?php

namespace Agentic\Knowledge\Contracts;

use Agentic\Knowledge\KnowledgeChunk;

/**
 * Vector storage abstraction — provider-independent.
 */
interface VectorStore
{
    /**
     * @param  list<float>  $vector
     * @param  array<string, mixed>  $metadata
     */
    public function upsert(string $id, array $vector, KnowledgeChunk $chunk, array $metadata = []): void;

    /**
     * @param  list<float>  $vector
     * @return list<KnowledgeChunk>
     */
    public function search(array $vector, int $limit = 5, ?string $namespace = null): array;

    public function deleteNamespace(string $namespace): void;
}
