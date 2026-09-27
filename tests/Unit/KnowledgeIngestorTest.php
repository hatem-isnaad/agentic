<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\KnowledgeIngestor;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Persistence\InMemory\InMemoryKnowledgeRepository;
use Agentic\Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class KnowledgeIngestorTest extends TestCase
{
    public function test_ingest_parses_documents_and_reindexes_vector_source(): void
    {
        $repository = new InMemoryKnowledgeRepository();
        $repository->seed(new KnowledgeSourceDefinition(
            slug: 'policies',
            name: 'Policies',
            driver: 'vector',
            configuration: [],
            status: 'published',
        ));

        $this->app->instance(\Agentic\Contracts\Repositories\KnowledgeRepository::class, $repository);

        $source = app(KnowledgeIngestor::class)->ingest('policies', [
            'format' => 'markdown',
            'raw_text' => "# Returns\n\nAccepted within 30 days.",
        ]);

        $this->assertIsArray($source->configuration['documents'] ?? null);
        $this->assertNotEmpty($source->configuration['documents']);
    }

    public function test_ingest_fetches_url_documents_before_parsing(): void
    {
        config()->set('agentic.http.allow_unresolved_hosts', true);

        Http::preventStrayRequests();
        Http::fake([
            'https://docs.example.test/*' => Http::response('# Policy\n\nThirty day returns.', 200),
        ]);

        $repository = new InMemoryKnowledgeRepository();
        $repository->seed(new KnowledgeSourceDefinition(
            slug: 'policies',
            name: 'Policies',
            driver: 'vector',
            configuration: [],
            status: 'published',
        ));

        $this->app->instance(\Agentic\Contracts\Repositories\KnowledgeRepository::class, $repository);

        $source = app(KnowledgeIngestor::class)->ingest('policies', [
            'format' => 'markdown',
            'urls' => ['https://docs.example.test/policy'],
        ]);

        $this->assertNotEmpty($source->configuration['documents'] ?? null);
        $this->assertStringContainsString('Thirty day returns', implode("\n", $source->configuration['documents']));
    }
}
