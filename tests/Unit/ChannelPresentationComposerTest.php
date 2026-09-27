<?php

namespace Agentic\Tests\Unit;

use Agentic\Context\RuntimeContext;
use Agentic\Reply\ChannelPresentationComposer;
use Agentic\Reply\ReplyChannel;
use Agentic\Tests\TestCase;

final class ChannelPresentationComposerTest extends TestCase
{
    public function test_widget_channel_asks_for_markdown_not_whatsapp(): void
    {
        $text = (new ChannelPresentationComposer())->compose(
            'Help users.',
            new RuntimeContext(['channel' => 'widget']),
        );

        $this->assertStringContainsString('Help users.', $text);
        $this->assertStringContainsString('web chat', $text);
        $this->assertStringContainsString('Markdown', $text);
        $this->assertStringContainsString('not WhatsApp', $text);
    }

    public function test_whatsapp_channel_forbids_html(): void
    {
        $text = (new ChannelPresentationComposer())->block(ReplyChannel::WhatsApp);

        $this->assertStringContainsString('WhatsApp', $text);
        $this->assertStringContainsString('No HTML', $text);
    }
}
