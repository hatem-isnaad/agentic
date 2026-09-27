<?php

namespace Agentic\Widget\Services;

use Agentic\Models\BroadcastEvent;
use Agentic\Widget\Support\WidgetReplyDelivery;

final class WidgetRealtimeService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function eventsSince(string $conversationId, int $sinceId = 0, int $limit = 20): array
    {
        $limit = min(50, max(1, $limit));

        return BroadcastEvent::query()
            ->where('channel', WidgetReplyDelivery::conversationChannel($conversationId))
            ->when($sinceId > 0, fn ($q) => $q->where('id', '>', $sinceId))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (BroadcastEvent $event) => [
                'id' => $event->id,
                'event' => $event->event,
                'payload' => $event->payload,
                'created_at' => optional($event->created_at)?->toISOString(),
            ])
            ->all();
    }
}
