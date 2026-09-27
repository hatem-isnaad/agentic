<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\RagValidator;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class RagValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.knowledge.driver', 'eloquent');
        $app['config']->set('agentic.knowledge.embedding', 'deterministic');
        $app['config']->set('agentic.knowledge.vector_store', 'pgvector');
    }

    public function test_rag_validator_passes_with_offline_embeddings_and_pgvector_store(): void
    {
        $validator = app(RagValidator::class);

        $result = $validator->validate($validator->offlineEmbeddings());

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['top_score']);
    }
}
