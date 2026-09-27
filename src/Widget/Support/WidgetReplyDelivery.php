<?php

namespace Agentic\Widget\Support;

/**
 * Widget assistant replies: sync HTTP body vs async queue + realtime (Pusher/polling).
 */
final class WidgetReplyDelivery
{
    public static function isAsync(): bool
    {
        $configured = config('agentic.widget.async_replies');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return (string) config('agentic.widget.broadcast.driver', 'polling') === 'pusher';
    }

    public static function conversationChannel(string $conversationId): string
    {
        $prefix = (string) config('agentic.widget.broadcast.channel_prefix', 'agentic-widget');

        return $prefix.'.'.$conversationId;
    }
}
