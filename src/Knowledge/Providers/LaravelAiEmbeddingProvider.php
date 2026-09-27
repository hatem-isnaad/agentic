<?php

namespace Agentic\Knowledge\Providers;

use Agentic\Knowledge\Contracts\EmbeddingProvider;
use Laravel\Ai\Embeddings;

/**
 * Delegates embedding generation to the Laravel AI SDK.
 */
final class LaravelAiEmbeddingProvider implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0] ?? [];
    }

    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = Embeddings::for($texts)->generate(
            provider: config('agentic.knowledge.embedding_provider'),
            model: config('agentic.knowledge.embedding_model'),
        );

        return $response->embeddings;
    }
}
