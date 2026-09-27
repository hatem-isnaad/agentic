<?php

namespace Agentic\Knowledge\Documents\Parsers;

use Agentic\Knowledge\Documents\PdfTextExtractor;
use Agentic\Knowledge\Documents\SmalotPdfTextExtractor;
use InvalidArgumentException;

final class PdfDocumentParser implements DocumentParser
{
    public function __construct(
        private ?PdfTextExtractor $extractor = null,
    ) {}

    public function supports(string $format): bool
    {
        return strtolower($format) === 'pdf';
    }

    public function parse(mixed $payload): array
    {
        $extractor = $this->extractor ?? new SmalotPdfTextExtractor();
        $documents = [];

        if (is_string($payload) && $payload !== '') {
            $text = $extractor->extract($payload);

            return $text === '' ? [] : [$text];
        }

        if (! is_array($payload)) {
            return [];
        }

        foreach ($payload as $entry) {
            if (is_string($entry) && $entry !== '') {
                $text = $extractor->extract($entry);

                if ($text !== '') {
                    $documents[] = $text;
                }

                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $content = $entry['content'] ?? null;

            if (! is_string($content) || $content === '') {
                continue;
            }

            $text = $extractor->extract($content);

            if ($text === '') {
                continue;
            }

            $title = (string) ($entry['title'] ?? '');

            $documents[] = $title !== '' ? $title."\n\n".$text : $text;
        }

        if ($documents === [] && $payload !== []) {
            throw new InvalidArgumentException('PDF ingest requires binary strings or {content} document entries.');
        }

        return $documents;
    }
}
