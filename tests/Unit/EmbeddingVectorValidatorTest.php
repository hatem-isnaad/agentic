<?php

namespace Agentic\Tests\Unit;

use Agentic\Exceptions\EmbeddingConfigurationException;
use Agentic\Knowledge\Support\EmbeddingVectorValidator;
use Agentic\Tests\TestCase;

final class EmbeddingVectorValidatorTest extends TestCase
{
    public function test_rejects_empty_vector(): void
    {
        $this->expectException(EmbeddingConfigurationException::class);
        EmbeddingVectorValidator::assertUsable([]);
    }

    public function test_rejects_zero_vector(): void
    {
        $this->expectException(EmbeddingConfigurationException::class);
        EmbeddingVectorValidator::assertUsable([0.0, 0.0]);
    }

    public function test_enforces_expected_dimensions(): void
    {
        $this->expectException(EmbeddingConfigurationException::class);
        EmbeddingVectorValidator::assertUsable([0.5, 0.25], 3);
    }
}
