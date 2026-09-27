<?php

namespace Agentic\Knowledge\Documents;

use Agentic\Knowledge\Documents\Parsers\DocumentParser;
use Agentic\Knowledge\Documents\Parsers\HtmlDocumentParser;
use Agentic\Knowledge\Documents\Parsers\JsonDocumentParser;
use Agentic\Knowledge\Documents\Parsers\MarkdownDocumentParser;
use Agentic\Knowledge\Documents\Parsers\PdfDocumentParser;
use Agentic\Knowledge\Documents\Parsers\PlainTextDocumentParser;
use InvalidArgumentException;

final class DocumentParserResolver
{
    /** @var list<DocumentParser> */
    private array $parsers;

    public function __construct()
    {
        $this->parsers = [
            new JsonDocumentParser(),
            new MarkdownDocumentParser(),
            new HtmlDocumentParser(),
            new PdfDocumentParser(),
            new PlainTextDocumentParser(),
        ];
    }

    public function parse(string $format, mixed $payload): array
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($format)) {
                return $parser->parse($payload);
            }
        }

        throw new InvalidArgumentException("Unsupported knowledge document format [{$format}].");
    }
}
