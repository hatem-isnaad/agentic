<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Widget\Support\WidgetReplyDelivery;

final class WidgetReplyDeliveryTest extends TestCase
{
    public function test_async_is_auto_enabled_for_pusher_driver(): void
    {
        config()->set('agentic.widget.async_replies', null);
        config()->set('agentic.widget.broadcast.driver', 'pusher');

        $this->assertTrue(WidgetReplyDelivery::isAsync());
    }

    public function test_async_can_be_forced_off_when_pusher(): void
    {
        config()->set('agentic.widget.async_replies', false);
        config()->set('agentic.widget.broadcast.driver', 'pusher');

        $this->assertFalse(WidgetReplyDelivery::isAsync());
    }

    public function test_polling_defaults_to_sync(): void
    {
        config()->set('agentic.widget.async_replies', null);
        config()->set('agentic.widget.broadcast.driver', 'polling');

        $this->assertFalse(WidgetReplyDelivery::isAsync());
    }
}
