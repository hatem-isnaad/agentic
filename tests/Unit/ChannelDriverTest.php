<?php

namespace Agentic\Tests\Unit;

use Agentic\Channels\ChannelDriver;
use Agentic\Channels\ChannelKind;
use Agentic\Tests\TestCase;

final class ChannelDriverTest extends TestCase
{
    public function test_widget_only_allows_embed(): void
    {
        $this->assertSame([ChannelDriver::Embed], ChannelDriver::allowedFor(ChannelKind::Widget));
    }

    public function test_whatsapp_allows_meta_and_webjs(): void
    {
        $this->assertSame(
            [ChannelDriver::MetaCloud, ChannelDriver::WebJs],
            ChannelDriver::allowedFor(ChannelKind::WhatsApp),
        );
    }
}
