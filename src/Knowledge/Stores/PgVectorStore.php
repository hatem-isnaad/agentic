<?php

namespace Agentic\Knowledge\Stores;

use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\Support\CosineSimilarity;
use Agentic\Models\VectorEntry;

/**
 * Database-backed vector store (JSON vectors + cosine ranking).
 *
 * Suitable for moderate corpora and Postgres/MySQL/SQLite during development.
 * For large-scale search, use the Pinecone driver or native pgvector indexes.
 */
final class PgVectorStore implements VectorStore
{
    public function upsert(string $id, array $vector, KnowledgeChunk $chunk, array $metadata = []): void
    {
        $namespace = is_string($metadata['namespace'] ?? null) ? $metadata['namespace'] : 'default';

        VectorEntry::query()->updateOrCreate(
            ['id' => $id],
            [
                'namespace' => $namespace,
                'vector' => $vector,
                'content' => $chunk->content,
                'source' => $chunk->source,
                'metadata' => $metadata === [] ? null : $metadata,
            ],
        );
    }

    public function search(array $vector, int $limit = 5, ?string $namespace = null): array
    {
        $query = VectorEntry::query();

        if ($namespace !== null && $namespace !== '') {
            $query->where('namespace', $namespace);
        }

        $entries = $query->get();

        $scored = [];

        foreach ($entries as $entry) {
            $entryVector = $entry->vector ?? [];

            if (! is_array($entryVector)) {
                continue;
            }

            $score = CosineSimilarity::score($vector, $entryVector);

            $scored[] = new KnowledgeChunk(
                content: (string) $entry->content,
                source: $entry->source,
                score: $score,
                metadata: is_array($entry->metadata) ? $entry->metadata : [],
            );
        }

        usort($scored, fn (KnowledgeChunk $a, KnowledgeChunk $b) => ($b->score ?? 0) <=> ($a->score ?? 0));

        return array_slice($scored, 0, $limit);
    }

    public function deleteNamespace(string $namespace): void
    {
        VectorEntry::query()->where('namespace', $namespace)->delete();
    }
}
