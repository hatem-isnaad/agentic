<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Models\BroadcastEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RealtimeController
{
    public function __invoke(Request $request, string $conversationId): JsonResponse
    {
        $sinceId = (int) $request->query('since_id', 0);
        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $prefix = (string) config('agentic.widget.broadcast.channel_prefix', 'agentic-widget');
        $channel = $prefix.'.'.$conversationId;

        $events = BroadcastEvent::query()
            ->where('channel', $channel)
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

        return response()->json(['data' => $events]);
    }
}
