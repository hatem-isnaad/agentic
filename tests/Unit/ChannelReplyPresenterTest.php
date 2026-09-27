<?php

namespace Agentic\Tests\Unit;

use Agentic\Reply\ChannelReplyPresenterFactory;
use Agentic\Tests\TestCase;
use Agentic\Widget\Reply\HtmlReplyRenderer;
use Agentic\Widget\Reply\StructuredReplyBuilder;

final class ChannelReplyPresenterTest extends TestCase
{
    public function test_widget_channel_returns_html(): void
    {
        $presented = $this->factory()->forChannel('widget')->present('**Hello**');

        $this->assertStringContainsString('<strong>Hello</strong>', $presented->html);
        $this->assertSame('Hello', $presented->text);
    }

    public function test_whatsapp_channel_uses_whatsapp_markers_not_html(): void
    {
        $presented = $this->factory()->forChannel('whatsapp')->present('**Hello** and *now*');

        $this->assertSame('', $presented->html);
        $this->assertSame('*Hello* and _now_', $presented->text);
        $this->assertSame('text', $presented->format);
    }

    private function factory(): ChannelReplyPresenterFactory
    {
        return new ChannelReplyPresenterFactory(new StructuredReplyBuilder(new HtmlReplyRenderer()));
    }
}
