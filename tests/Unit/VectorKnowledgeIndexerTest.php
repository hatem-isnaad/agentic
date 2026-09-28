<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Agentic\Knowledge\Indexers\VectorKnowledgeIndexer;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Knowledge\Retrievers\VectorKnowledgeRetriever;
use Agentic\Knowledge\Stores\ArrayVectorStore;
use Agentic\Tests\TestCase;

final class VectorKnowledgeIndexerTest extends TestCase
{
    public function test_index_chunks_documents_and_reindex_replaces_namespace(): void
    {
        $store = new ArrayVectorStore;
        $embeddings = new class implements EmbeddingProvider
        {
            public function embed(string $text): array
            {
                return [crc32($text) % 100 / 100];
            }

            public function embedMany(array $texts): array
            {
                return array_map(fn (string $text) => $this->embed($text), $texts);
            }
        };

        $indexer = new VectorKnowledgeIndexer($embeddings, $store);
        $source = new KnowledgeSourceDefinition(
            slug: 'policies',
            name: 'Policies',
            driver: 'vector',
            configuration: [
                'raw_text' => str_repeat('Returns accepted within 30 days. ', 30),
                'chunk_size' => 80,
                'chunk_overlap' => 10,
            ],
        );

        $indexer->index($source);

        $retriever = new VectorKnowledgeRetriever($embeddings, $store);
        $first = $retriever->retrieve($source, 'returns within 30 days', 50);

        $indexer->index($source);
        $second = $retriever->retrieve($source, 'returns within 30 days', 50);

        $this->assertGreaterThan(1, count($first));
        $this->assertCount(count($first), $second);

        $chunks = $retriever->retrieve($source, 'returns within 30 days', 3);

        $this->assertNotEmpty($chunks);
        $this->assertStringContainsString('30 days', $chunks[0]->content);
    }

    public function test_search_is_limited_to_the_source_namespace(): void
    {
        $store = new ArrayVectorStore;
        $embeddings = new class implements EmbeddingProvider
        {
            public function embed(string $text): array
            {
                return [0.5];
            }

            public function embedMany(array $texts): array
            {
                return array_map(fn () => [0.5], $texts);
            }
        };

        $indexer = new VectorKnowledgeIndexer($embeddings, $store);
        $indexer->index(new KnowledgeSourceDefinition(
            slug: 'alpha-docs',
            name: 'A',
            driver: 'vector',
            configuration: [
                'documents' => ['Alpha one', 'Alpha two', 'Alpha three'],
            ],
        ));
        $indexer->index(new KnowledgeSourceDefinition(
            slug: 'beta-docs',
            name: 'B',
            driver: 'vector',
            configuration: [
                'documents' => ['Beta only document'],
            ],
        ));

        $retriever = new VectorKnowledgeRetriever($embeddings, $store);
        $beta = $retriever->retrieve(
            new KnowledgeSourceDefinition(slug: 'beta-docs', name: 'Docs', driver: 'vector'),
            'document',
            5,
        );
        $alpha = $retriever->retrieve(
            new KnowledgeSourceDefinition(slug: 'alpha-docs', name: 'Docs', driver: 'vector'),
            'document',
            5,
        );

        $this->assertCount(1, $beta);
        $this->assertStringContainsString('Beta', $beta[0]->content);
        $this->assertCount(3, $alpha);
    }
}
