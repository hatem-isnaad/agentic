<?php

namespace Agentic\Knowledge\Stores;

use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\Support\EmbeddingVectorValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * PostgreSQL pgvector extension store (cosine distance via <=>).
 *
 * Requires migration `2026_09_27_120000_add_pgvector_embedding_column` and CREATE EXTENSION vector.
 */
final class PostgresPgvectorStore implements VectorStore
{
    public function upsert(string $id, array $vector, KnowledgeChunk $chunk, array $metadata = []): void
    {
        $this->assertReady();

        $dimensions = (int) config('agentic.knowledge.pgvector.dimensions', 1536);
        EmbeddingVectorValidator::assertUsable($vector, $dimensions);

        $namespace = is_string($metadata['namespace'] ?? null) ? $metadata['namespace'] : 'default';
        $vectorLiteral = $this->toVectorLiteral($vector);
        $metadataJson = $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR);
        $vectorJson = json_encode($vector, JSON_THROW_ON_ERROR);

        DB::insert(
            'INSERT INTO agentic_vector_entries (id, namespace, vector, content, source, metadata, embedding, created_at, updated_at)
             VALUES (?, ?, ?::json, ?, ?, ?::json, ?::vector, NOW(), NOW())
             ON CONFLICT (id) DO UPDATE SET
                namespace = EXCLUDED.namespace,
                vector = EXCLUDED.vector,
                content = EXCLUDED.content,
                source = EXCLUDED.source,
                metadata = EXCLUDED.metadata,
                embedding = EXCLUDED.embedding,
                updated_at = NOW()',
            [
                $id,
                $namespace,
                $vectorJson,
                $chunk->content,
                $chunk->source,
                $metadataJson,
                $vectorLiteral,
            ],
        );
    }

    public function search(array $vector, int $limit = 5, ?string $namespace = null): array
    {
        $this->assertReady();

        $dimensions = (int) config('agentic.knowledge.pgvector.dimensions', 1536);
        EmbeddingVectorValidator::assertUsable($vector, $dimensions);

        $vectorLiteral = $this->toVectorLiteral($vector);
        $bindings = [$vectorLiteral, $vectorLiteral];

        $sql = 'SELECT content, source, metadata, (1 - (embedding <=> ?::vector)) AS score
                FROM agentic_vector_entries
                WHERE embedding IS NOT NULL';

        if ($namespace !== null && $namespace !== '') {
            $sql .= ' AND namespace = ?';
            $bindings[] = $namespace;
        }

        $sql .= ' ORDER BY embedding <=> ?::vector ASC LIMIT ?';
        $bindings[] = $vectorLiteral;
        $bindings[] = max(1, $limit);

        $rows = DB::select($sql, $bindings);

        $chunks = [];

        foreach ($rows as $row) {
            $metadata = is_string($row->metadata ?? null)
                ? json_decode($row->metadata, true, 512, JSON_THROW_ON_ERROR)
                : (is_array($row->metadata ?? null) ? $row->metadata : []);

            $chunks[] = new KnowledgeChunk(
                content: (string) $row->content,
                source: is_string($row->source ?? null) ? $row->source : null,
                score: isset($row->score) ? (float) $row->score : null,
                metadata: is_array($metadata) ? $metadata : [],
            );
        }

        return $chunks;
    }

    public function deleteNamespace(string $namespace): void
    {
        DB::table('agentic_vector_entries')->where('namespace', $namespace)->delete();
    }

    public function isReady(): bool
    {
        if (DB::getDriverName() !== 'pgsql') {
            return false;
        }

        if (! Schema::hasColumn('agentic_vector_entries', 'embedding')) {
            return false;
        }

        $extension = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'vector' LIMIT 1");

        return $extension !== null;
    }

    private function assertReady(): void
    {
        if (! $this->isReady()) {
            throw new RuntimeException(
                'Postgres pgvector store is not ready. Run migrations on PostgreSQL and CREATE EXTENSION vector.',
            );
        }
    }

    /**
     * @param  list<float|int>  $vector
     */
    private function toVectorLiteral(array $vector): string
    {
        $parts = array_map(fn ($v) => is_numeric($v) ? (string) (float) $v : '0', $vector);

        return '['.implode(',', $parts).']';
    }
}
