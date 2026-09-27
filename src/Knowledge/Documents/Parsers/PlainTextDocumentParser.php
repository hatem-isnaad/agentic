<?php

namespace Agentic\Knowledge\Documents\Parsers;

final class PlainTextDocumentParser implements DocumentParser
{
    public function supports(string $format): bool
    {
        return in_array(strtolower($format), ['text', 'plain', 'txt'], true);
    }

    public function parse(mixed $payload): array
    {
        if (is_string($payload)) {
            return trim($payload) === '' ? [] : [trim($payload)];
        }

        if (! is_array($payload)) {
            return [];
        }

        $documents = [];

        foreach ($payload as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $documents[] = trim($entry);
            }
        }

        return $documents;
    }
}
