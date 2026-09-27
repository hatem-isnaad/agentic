<?php

namespace Agentic\Knowledge\Documents;

use InvalidArgumentException;
use Smalot\PdfParser\Parser;

final class SmalotPdfTextExtractor implements PdfTextExtractor
{
    public function extract(string $binary): string
    {
        if (! class_exists(Parser::class)) {
            throw new InvalidArgumentException(
                'PDF parsing requires smalot/pdfparser. Run: composer require smalot/pdfparser',
            );
        }

        $parser = new Parser();
        $text = trim($parser->parseContent($binary)->getText());

        return $text;
    }
}
