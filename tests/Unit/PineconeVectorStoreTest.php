<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\Stores\PineconeVectorStore;
use Agentic\Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class PineconeVectorStoreTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.knowledge.pinecone.host', 'https://example.pinecone.io');
        $app['config']->set('agentic.knowledge.pinecone.api_key', 'test-key');
    }

    public function test_query_maps_matches_to_knowledge_chunks(): void
    {
        Http::fake([
            'https://example.pinecone.io/query' => Http::response([
                'matches' => [[
                    'score' => 0.91,
                    'metadata' => [
                        'content' => 'Policy text',
                        'source' => 'policies',
                    ],
                ]],
            ]),
        ]);

        $chunks = (new PineconeVectorStore())->search([0.1, 0.2], 3, 'default');

        $this->assertCount(1, $chunks);
        $this->assertSame('Policy text', $chunks[0]->content);
        $this->assertSame('policies', $chunks[0]->source);
    }
}
