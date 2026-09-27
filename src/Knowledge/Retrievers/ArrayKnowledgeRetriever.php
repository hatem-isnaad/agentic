<?php

namespace Agentic\Knowledge\Retrievers;

use Agentic\Context\RuntimeContext;
use Agentic\Knowledge\Contracts\Retriever;
use Agentic\Knowledge\KnowledgeChunk;
use Agentic\Knowledge\KnowledgeSourceDefinition;

/**
 * Simple keyword-overlap retrieval from configured document snippets.
 */
final class ArrayKnowledgeRetriever implements Retriever
{
    public function supports(KnowledgeSourceDefinition $source): bool
    {
        return $source->driver === 'array';
    }

    public function retrieve(
        KnowledgeSourceDefinition $source,
        string $query,
        int $limit = 5,
        ?RuntimeContext $runtime = null,
    ): array {
        unset($runtime);

        $documents = $source->configuration['documents'] ?? [];

        if (! is_array($documents)) {
            return [];
        }

        $queryTerms = $this->terms($query);
        $scored = [];

        foreach ($documents as $index => $document) {
            if (is_string($document)) {
                $content = $document;
                $meta = [];
            } elseif (is_array($document)) {
                $content = (string) ($document['content'] ?? '');
                $meta = $document;
                unset($meta['content']);
            } else {
                continue;
            }

            if ($content === '') {
                continue;
            }

            $score = $this->score($queryTerms, $this->terms($content));

            if ($score <= 0) {
                continue;
            }

            $scored[] = new KnowledgeChunk(
                content: $content,
                source: $source->slug,
                score: $score,
                metadata: array_merge($meta, ['index' => $index]),
            );
        }

        usort($scored, fn (KnowledgeChunk $a, KnowledgeChunk $b) => ($b->score ?? 0) <=> ($a->score ?? 0));

        return array_slice($scored, 0, $limit);
    }

    /**
     * @return list<string>
     */
    private function terms(string $text): array
    {
        $parts = preg_split('/\W+/u', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? array_values(array_unique($parts)) : [];
    }

    /**
     * @param  list<string>  $queryTerms
     * @param  list<string>  $contentTerms
     */
    private function score(array $queryTerms, array $contentTerms): float
    {
        if ($queryTerms === [] || $contentTerms === []) {
            return 0.0;
        }

        $overlap = count(array_intersect($queryTerms, $contentTerms));

        return $overlap / max(1, count($queryTerms));
    }
}
