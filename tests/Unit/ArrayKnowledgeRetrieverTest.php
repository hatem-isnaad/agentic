<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Knowledge\Retrievers\ArrayKnowledgeRetriever;
use Agentic\Tests\TestCase;

final class ArrayKnowledgeRetrieverTest extends TestCase
{
    public function test_retrieves_documents_matching_query_terms(): void
    {
        $retriever = new ArrayKnowledgeRetriever();

        $source = new KnowledgeSourceDefinition(
            slug: 'faq',
            name: 'FAQ',
            driver: 'array',
            configuration: [
                'documents' => [
                    'Refunds are processed within 5 business days.',
                    'Shipping updates appear in your account dashboard.',
                ],
            ],
        );

        $chunks = $retriever->retrieve($source, 'How long do refunds take?');

        $this->assertNotEmpty($chunks);
        $this->assertStringContainsString('Refunds', $chunks[0]->content);
        $this->assertSame('faq', $chunks[0]->source);
    }
}
