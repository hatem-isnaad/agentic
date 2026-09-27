<?php

namespace Agentic\Knowledge\Chunking;

final class TextChunker
{
    /**
     * @return list<string>
     */
    public function chunk(string $text, int $size = 800, int $overlap = 120): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($normalized === '') {
            return [];
        }

        $size = max(100, $size);
        $overlap = max(0, min($overlap, $size - 1));

        if (strlen($normalized) <= $size) {
            return [$normalized];
        }

        $chunks = [];
        $offset = 0;
        $length = strlen($normalized);

        while ($offset < $length) {
            $piece = substr($normalized, $offset, $size);

            if ($piece === '') {
                break;
            }

            $chunks[] = $piece;
            $offset += $size - $overlap;
        }

        return $chunks;
    }
}
