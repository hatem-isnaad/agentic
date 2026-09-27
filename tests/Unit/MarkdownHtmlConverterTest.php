<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Widget\Reply\HtmlReplyRenderer;
use Agentic\Widget\Reply\MarkdownHtmlConverter;

final class MarkdownHtmlConverterTest extends TestCase
{
    public function test_bold_and_lists_become_html(): void
    {
        $html = (new MarkdownHtmlConverter())->convert("📦 **Outbound**\n\n- First\n- Second");

        $this->assertStringContainsString('<strong>Outbound</strong>', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
        $this->assertStringNotContainsString('**Outbound**', $html);
    }

    public function test_renderer_turns_plain_markdown_into_html(): void
    {
        $html = (new HtmlReplyRenderer())->render('Hello **world**');

        $this->assertStringContainsString('<strong>world</strong>', $html);
        $this->assertStringNotContainsString('**world**', $html);
    }

    public function test_script_tags_are_stripped_from_html_blocks(): void
    {
        $html = (new HtmlReplyRenderer())->render([
            'format' => 'html',
            'html' => '<p>Safe</p><script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('Safe', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}
