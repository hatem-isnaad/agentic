<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Providers\DeterministicEmbeddingProvider;
use Agentic\Knowledge\Support\CosineSimilarity;
use Agentic\Tests\TestCase;

final class DeterministicEmbeddingProviderTest extends TestCase
{
    public function test_similar_text_produces_closer_vectors_than_unrelated_text(): void
    {
        $provider = new DeterministicEmbeddingProvider(32);

        $policy = $provider->embed('refund policy within thirty days');
        $query = $provider->embed('refund within thirty days');
        $noise = $provider->embed('database migration checksum');

        $this->assertGreaterThan(
            CosineSimilarity::score($query, $noise),
            CosineSimilarity::score($query, $policy),
        );
    }
}
