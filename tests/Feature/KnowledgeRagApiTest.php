<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class KnowledgeRagApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.knowledge.driver', 'eloquent');
        $app['config']->set('agentic.knowledge.embedding', 'deterministic');
        $app['config']->set('agentic.knowledge.vector_store', 'pgvector');
    }

    public function test_ingest_and_admin_search_returns_relevant_chunk(): void
    {
        $admin = trim((string) config('agentic.admin.api.prefix'), '/');
        $token = 'unique-warranty-token-'.bin2hex(random_bytes(3));

        $this->postJson('/'.$admin.'/knowledge-sources', [
            'name' => 'Warranty',
            'slug' => 'warranty',
            'driver' => 'vector',
            'status' => 'published',
            'config' => ['documents' => []],
        ])->assertCreated();

        $this->postJson('/'.$admin.'/knowledge-sources/warranty/ingest', [
            'format' => 'text',
            'raw_text' => "Extended warranty covers {$token} for two years.",
            'reindex' => true,
        ])->assertOk();

        $search = $this->postJson('/'.$admin.'/knowledge-sources/warranty/search', [
            'query' => "What does the warranty say about {$token}?",
            'limit' => 3,
        ]);

        $search->assertOk();
        $this->assertStringContainsString(
            $token,
            (string) $search->json('data.0.content'),
        );
        $this->assertNotNull($search->json('data.0.score'));
    }
}
