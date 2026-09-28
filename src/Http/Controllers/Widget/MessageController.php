<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Http\Requests\Widget\StoreWidgetMessageRequest;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Widget\Services\WidgetMessageService;
use Illuminate\Http\JsonResponse;

final class MessageController
{
    public function __construct(private WidgetMessageService $messages) {}

    public function store(StoreWidgetMessageRequest $request, ?string $id = null): JsonResponse
    {
        $result = $this->messages->send($request->toData($id));

        return JsonApiResponse::data($result, isset($result['success']) && $result['success'] !== true ? 422 : 200);
    }
}
