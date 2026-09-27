<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Http\Requests\Widget\StoreWidgetConversationRequest;
use Agentic\Http\Requests\Widget\WidgetAgentQueryRequest;
use Agentic\Http\Requests\Widget\WidgetHistoryRequest;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\Services\WidgetConversationService;
use Illuminate\Http\JsonResponse;

final class ConversationController
{
    public function __construct(
        private WidgetConversationService $conversations,
    ) {}

    public function index(WidgetAgentQueryRequest $request): JsonResponse
    {
        return JsonApiResponse::data($this->conversations->listForAgent(
            $request->agentSlug(),
            WidgetIdentity::fromRequest($request),
        ));
    }

    public function store(StoreWidgetConversationRequest $request): JsonResponse
    {
        $data = $request->toData();

        return JsonApiResponse::created($this->conversations->create($data->agent, $data->identity, $data->metadata));
    }

    public function messages(WidgetHistoryRequest $request, string $id): JsonResponse
    {
        $page = $this->conversations->messagesPage(
            $id,
            WidgetIdentity::fromRequest($request),
            $request->limit(),
            $request->beforeCursor(),
        );

        return JsonApiResponse::data($page['messages'], meta: $page['meta']);
    }
}
