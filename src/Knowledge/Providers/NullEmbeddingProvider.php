<?php

namespace Agentic\Knowledge\Providers;

use Agentic\Knowledge\Contracts\EmbeddingProvider;

/**
 * Placeholder embedding provider for tests and offline indexing flows.
 */
final class NullEmbeddingProvider implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        return [0.0];
    }

    public function embedMany(array $texts): array
    {
        return array_map(fn () => [0.0], $texts);
    }
}
