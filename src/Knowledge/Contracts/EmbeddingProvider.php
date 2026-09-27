<?php

namespace Agentic\Knowledge\Contracts;

/**
 * Generates vector embeddings for text.
 *
 * Implementations may delegate to Laravel AI SDK embedding APIs.
 */
interface EmbeddingProvider
{
    /**
     * @return list<float>
     */
    public function embed(string $text): array;

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embedMany(array $texts): array;
}
