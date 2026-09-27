<?php

namespace Agentic\Knowledge\Documents\Parsers;

final class MarkdownDocumentParser implements DocumentParser
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'markdown';
    }

    public function parse(mixed $payload): array
    {
        $texts = (new PlainTextDocumentParser())->parse($payload);
        $normalized = [];

        foreach ($texts as $text) {
            $clean = preg_replace('/```[\s\S]*?```/m', ' ', $text) ?? $text;
            $clean = preg_replace('/`([^`]+)`/', '$1', $clean) ?? $clean;
            $clean = preg_replace('/[#>*_\-\[\]\(\)!]/', ' ', $clean) ?? $clean;
            $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);

            if ($clean !== '') {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }
}
