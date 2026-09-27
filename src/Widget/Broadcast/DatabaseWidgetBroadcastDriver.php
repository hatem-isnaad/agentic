<?php

namespace Agentic\Widget\Broadcast;

use Agentic\Models\BroadcastEvent;

final class DatabaseWidgetBroadcastDriver implements WidgetBroadcastDriver
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $channel, string $event, array $payload): void
    {
        BroadcastEvent::query()->create([
            'channel' => $channel,
            'event' => $event,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
