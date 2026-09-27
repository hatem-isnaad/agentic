<?php

namespace Agentic\Knowledge\Providers;

use Agentic\Knowledge\Contracts\EmbeddingProvider;

/**
 * Hash-based embeddings for offline tests and `agentic:rag-validate --offline`.
 *
 * Similar text (shared words) produces closer vectors without calling an AI API.
 */
final class DeterministicEmbeddingProvider implements EmbeddingProvider
{
    public function __construct(
        private int $dimensions = 32,
    ) {}

    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0] ?? array_fill(0, $this->dimensions, 0.0);
    }

    public function embedMany(array $texts): array
    {
        return array_map(fn (string $text) => $this->vectorize($text), $texts);
    }

    /**
     * @return list<float>
     */
    private function vectorize(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);
        $normalized = strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? ''));

        if ($normalized === '') {
            return $vector;
        }

        foreach (preg_split('/\s+/u', $normalized) ?: [] as $word) {
            if ($word === '') {
                continue;
            }

            $index = abs(crc32($word)) % $this->dimensions;
            $vector[$index] += 1.0;
        }

        $magnitude = sqrt(array_sum(array_map(fn (float $v): float => $v * $v, $vector)));

        if ($magnitude <= 0.0) {
            return $vector;
        }

        return array_map(fn (float $v): float => $v / $magnitude, $vector);
    }
}
