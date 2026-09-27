<?php

namespace Agentic\Knowledge\Documents\Parsers;

final class HtmlDocumentParser implements DocumentParser
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'html';
    }

    public function parse(mixed $payload): array
    {
        $texts = (new PlainTextDocumentParser())->parse($payload);
        $normalized = [];

        foreach ($texts as $text) {
            $clean = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);

            if ($clean !== '') {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }
}
