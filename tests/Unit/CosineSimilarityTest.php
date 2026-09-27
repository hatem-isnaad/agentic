<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Support\CosineSimilarity;
use Agentic\Tests\TestCase;

final class CosineSimilarityTest extends TestCase
{
    public function test_identical_vectors_score_one(): void
    {
        $this->assertSame(1.0, CosineSimilarity::score([1.0, 0.0], [1.0, 0.0]));
    }

    public function test_orthogonal_vectors_score_zero(): void
    {
        $this->assertSame(0.0, CosineSimilarity::score([1.0, 0.0], [0.0, 1.0]));
    }
}
