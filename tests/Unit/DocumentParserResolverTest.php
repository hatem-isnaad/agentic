<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Documents\DocumentParserResolver;
use Agentic\Tests\TestCase;

final class DocumentParserResolverTest extends TestCase
{
    public function test_markdown_parser_normalizes_headings(): void
    {
        $documents = (new DocumentParserResolver())->parse('markdown', "# Returns\n\nAccepted within **30 days**.");

        $this->assertCount(1, $documents);
        $this->assertStringContainsString('30 days', $documents[0]);
        $this->assertStringNotContainsString('#', $documents[0]);
    }

    public function test_json_parser_extracts_document_objects(): void
    {
        $documents = (new DocumentParserResolver())->parse('json', [
            ['title' => 'Policy', 'content' => 'No refunds after 14 days.'],
        ]);

        $this->assertSame('Policy'."\n\n".'No refunds after 14 days.', $documents[0]);
    }
}
