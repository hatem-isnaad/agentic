<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\KnowledgeIngestor;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Persistence\InMemory\InMemoryKnowledgeRepository;
use Agentic\Tests\TestCase;

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
}
