<?php

namespace Agentic\Tests\Unit;

use Agentic\Http\Support\KnowledgeIngestPayload;
use Agentic\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class KnowledgeIngestPayloadTest extends TestCase
{
    public function test_builds_documents_from_uploaded_pdfs(): void
    {
        $file = UploadedFile::fake()->createWithContent('policy.pdf', '%PDF-demo-content');

        $request = Request::create('/ingest', 'POST', [
            'format' => 'pdf',
            'reindex' => '1',
        ], [], [
            'files' => [$file],
        ]);

        $payload = KnowledgeIngestPayload::fromRequest($request);

        $this->assertSame('pdf', $payload['format']);
        $this->assertTrue($payload['reindex']);
        $this->assertCount(1, $payload['documents']);
        $this->assertSame('policy.pdf', $payload['documents'][0]['title']);
        $this->assertSame('%PDF-demo-content', $payload['documents'][0]['content']);
    }
}
