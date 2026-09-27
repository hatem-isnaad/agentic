<?php

namespace Agentic\Widget\Broadcast;

interface WidgetBroadcastDriver
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $channel, string $event, array $payload): void;
}
