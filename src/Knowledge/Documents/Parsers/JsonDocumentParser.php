<?php

namespace Agentic\Knowledge\Documents\Parsers;

use InvalidArgumentException;

final class JsonDocumentParser implements DocumentParser
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'json';
    }

    public function parse(mixed $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('JSON knowledge payload is invalid.');
            }

            return $this->parse($decoded);
        }

        if (! is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            $documents = [];

            foreach ($payload as $entry) {
                if (is_string($entry) && trim($entry) !== '') {
                    $documents[] = trim($entry);
                    continue;
                }

                if (is_array($entry)) {
                    $content = (string) ($entry['content'] ?? '');
                    $title = (string) ($entry['title'] ?? '');

                    if ($content !== '') {
                        $documents[] = $title !== '' ? $title."\n\n".$content : $content;
                    }
                }
            }

            return $documents;
        }

        if (isset($payload['documents']) && is_array($payload['documents'])) {
            return $this->parse($payload['documents']);
        }

        return [];
    }
}
