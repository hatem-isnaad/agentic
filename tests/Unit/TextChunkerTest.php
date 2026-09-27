<?php

namespace Agentic\Tests\Unit;

use Agentic\Knowledge\Chunking\TextChunker;
use Agentic\Tests\TestCase;

final class TextChunkerTest extends TestCase
{
    public function test_it_splits_long_text_with_overlap(): void
    {
        $text = str_repeat('word ', 200);
        $chunks = (new TextChunker())->chunk(trim($text), 100, 20);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertLessThanOrEqual(100, strlen($chunks[0]));
    }
}
