<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Exceptions\ConversationNotFoundException;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Widget\Services\WidgetMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HandoffController
{
    public function __construct(private WidgetMessageService $messages) {}

    public function store(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            return JsonApiResponse::data($this->messages->requestHandoff($id, (string) ($validated['reason'] ?? '')));
        } catch (ConversationNotFoundException) {
            return JsonApiResponse::error('Conversation not found.', 404);
        }
    }
}
