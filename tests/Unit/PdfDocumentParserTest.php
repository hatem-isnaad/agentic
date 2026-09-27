<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Documents\PdfTextExtractor;
use Agentic\Knowledge\Documents\Parsers\PdfDocumentParser;
use Agentic\Tests\TestCase;

final class PdfDocumentParserTest extends TestCase
{
    public function test_pdf_parser_extracts_text_via_injected_extractor(): void
    {
        $parser = new PdfDocumentParser(new class implements PdfTextExtractor {
            public function extract(string $binary): string
            {
                return 'Policy text from PDF';
            }
        });

        $documents = $parser->parse('%PDF-binary%');

        $this->assertSame(['Policy text from PDF'], $documents);
    }
}
