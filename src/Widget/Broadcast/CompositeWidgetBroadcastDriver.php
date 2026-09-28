<?php

namespace Agentic\Widget\Broadcast;

/**
 * Persists events for HTTP polling and optionally pushes to Pusher when configured.
 */
final class CompositeWidgetBroadcastDriver implements WidgetBroadcastDriver
{
    public function __construct(private DatabaseWidgetBroadcastDriver $database, private PusherWidgetBroadcastDriver $pusher) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $channel, string $event, array $payload): void
    {
        $driver = (string) config('agentic.widget.broadcast.driver', 'polling');

        if ($driver === 'polling' || $driver === 'pusher') {
            $this->database->publish($channel, $event, $payload);
        }

        if ($driver === 'pusher') {
            $this->pusher->publish($channel, $event, $payload);
        }
    }
}
