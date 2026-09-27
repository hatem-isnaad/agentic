<?php

namespace Agentic\Widget\Broadcast;

final class PusherWidgetBroadcastDriver implements WidgetBroadcastDriver
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $channel, string $event, array $payload): void
    {
        if ((string) config('agentic.widget.broadcast.driver', 'polling') !== 'pusher') {
            return;
        }

        $pusher = $this->client();

        if ($pusher === null) {
            return;
        }

        $pusher->trigger($channel, $event, $payload);
    }

    private function client(): ?\Pusher\Pusher
    {
        if (! class_exists(\Pusher\Pusher::class)) {
            return null;
        }

        $config = config('agentic.widget.broadcast.pusher', []);
        $key = (string) ($config['key'] ?? '');
        $secret = (string) ($config['secret'] ?? '');
        $appId = (string) ($config['app_id'] ?? '');
        $cluster = (string) ($config['cluster'] ?? 'mt1');

        if ($key === '' || $secret === '' || $appId === '') {
            return null;
        }

        return new \Pusher\Pusher(
            $key,
            $secret,
            $appId,
            [
                'cluster' => $cluster,
                'useTLS' => true,
            ],
        );
    }
}
