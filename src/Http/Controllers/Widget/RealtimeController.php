<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Widget\Services\WidgetRealtimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RealtimeController
{
    public function __construct(private WidgetRealtimeService $realtime) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        return JsonApiResponse::data($this->realtime->eventsSince($id, (int) $request->query('since_id', 0), (int) $request->query('limit', 20)));
    }
}
