<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\Stores\PgVectorStore;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PgVectorStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_highest_similarity_first(): void
    {
        $store = new PgVectorStore();

        $store->upsert('a', [1.0, 0.0], new KnowledgeChunk('alpha', 's'), ['namespace' => 'docs']);
        $store->upsert('b', [0.0, 1.0], new KnowledgeChunk('beta', 's'), ['namespace' => 'docs']);

        $results = $store->search([0.9, 0.1], 1, 'docs');

        $this->assertCount(1, $results);
        $this->assertSame('alpha', $results[0]->content);
    }
}
