<?php

namespace Agentic\Knowledge\Support;

use Agentic\Exceptions\EmbeddingConfigurationException;

final class EmbeddingVectorValidator
{
    /**
     * @param  list<float>  $vector
     */
    public static function assertUsable(array $vector, ?int $expectedDimensions = null): void
    {
        if ($vector === []) {
            throw new EmbeddingConfigurationException(
                'Embedding provider returned an empty vector. Set AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai and configure provider/model API keys.',
            );
        }

        $nonZero = false;

        foreach ($vector as $value) {
            if (! is_float($value) && ! is_int($value)) {
                throw new EmbeddingConfigurationException('Embedding vector contains non-numeric values.');
            }

            if ((float) $value !== 0.0) {
                $nonZero = true;
            }
        }

        if (! $nonZero) {
            throw new EmbeddingConfigurationException(
                'Embedding vector is all zeros. Use AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai (or deterministic for offline tests).',
            );
        }

        if ($expectedDimensions !== null && count($vector) !== $expectedDimensions) {
            throw new EmbeddingConfigurationException(
                "Embedding dimension mismatch: expected {$expectedDimensions}, got ".count($vector).'.',
            );
        }
    }
}
