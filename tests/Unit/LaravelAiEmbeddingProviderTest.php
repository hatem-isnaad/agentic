<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Providers\LaravelAiEmbeddingProvider;
use Agentic\Tests\TestCase;
use Laravel\Ai\Embeddings;

final class LaravelAiEmbeddingProviderTest extends TestCase
{
    public function test_delegates_to_laravel_ai_sdk_embeddings(): void
    {
        Embeddings::fake(fn () => [[0.5, 0.25]]);

        $vector = (new LaravelAiEmbeddingProvider())->embed('hello world');

        $this->assertSame([0.5, 0.25], $vector);

        Embeddings::assertGenerated(function ($prompt) {
            return $prompt->contains('hello world');
        });
    }
}
