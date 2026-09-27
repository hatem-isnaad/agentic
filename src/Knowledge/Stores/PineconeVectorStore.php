<?php

namespace Agentic\Knowledge\Stores;

use Agentic\Knowledge\Contracts\VectorStore;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\Support\EmbeddingVectorValidator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pinecone vector store adapter (HTTP API).
 */
final class PineconeVectorStore implements VectorStore
{
    public function upsert(string $id, array $vector, KnowledgeChunk $chunk, array $metadata = []): void
    {
        $dimensions = (int) config('agentic.knowledge.pinecone.dimensions', 1536);
        EmbeddingVectorValidator::assertUsable($vector, $dimensions);

        $namespace = is_string($metadata['namespace'] ?? null) ? $metadata['namespace'] : '';

        $response = Http::withHeaders($this->headers())
            ->post($this->endpoint('/vectors/upsert'), [
                'vectors' => [[
                    'id' => $id,
                    'values' => $vector,
                    'metadata' => array_merge($metadata, [
                        'content' => $chunk->content,
                        'source' => $chunk->source,
                    ]),
                ]],
                'namespace' => $namespace,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Pinecone upsert failed: '.$response->body());
        }
    }

    public function search(array $vector, int $limit = 5, ?string $namespace = null): array
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->endpoint('/query'), [
                'vector' => $vector,
                'topK' => $limit,
                'namespace' => $namespace ?? '',
                'includeMetadata' => true,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Pinecone query failed: '.$response->body());
        }

        $matches = $response->json('matches') ?? [];

        $chunks = [];

        foreach ($matches as $match) {
            if (! is_array($match)) {
                continue;
            }

            $metadata = is_array($match['metadata'] ?? null) ? $match['metadata'] : [];
            $content = (string) ($metadata['content'] ?? '');

            if ($content === '') {
                continue;
            }

            $chunks[] = new KnowledgeChunk(
                content: $content,
                source: is_string($metadata['source'] ?? null) ? $metadata['source'] : null,
                score: isset($match['score']) ? (float) $match['score'] : null,
                metadata: $metadata,
            );
        }

        return $chunks;
    }

    public function deleteNamespace(string $namespace): void
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->endpoint('/vectors/delete'), [
                'deleteAll' => true,
                'namespace' => $namespace,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Pinecone delete failed: '.$response->body());
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $apiKey = (string) config('agentic.knowledge.pinecone.api_key', '');

        return [
            'Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function endpoint(string $path): string
    {
        $host = rtrim((string) config('agentic.knowledge.pinecone.host', ''), '/');

        if ($host === '') {
            throw new RuntimeException('Pinecone host is not configured (agentic.knowledge.pinecone.host).');
        }

        return $host.$path;
    }
}
