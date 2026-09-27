<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Widget\Services\WidgetMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MessageController
{
    public function __construct(
        private WidgetMessageService $messages,
    ) {}

    public function store(Request $request): JsonResponse
    {
        return $this->handle($request, null);
    }

    public function storeForConversation(Request $request, string $id): JsonResponse
    {
        return $this->handle($request, $id);
    }

    private function handle(Request $request, ?string $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'agent' => ['required', 'string'],
            'message' => ['required', 'string'],
            'conversation_id' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $result = $this->messages->send(
            agentSlug: $validated['agent'],
            message: $validated['message'],
            conversationId: $conversationId ?? $validated['conversation_id'] ?? null,
            guestId: $request->header('X-Agentic-Guest-Id'),
            userId: $request->user()?->getAuthIdentifier(),
            tenantId: $request->header('X-Agentic-Tenant-Id'),
            metadata: $validated['metadata'] ?? [],
        );

        if (($result['success'] ?? false) !== true) {
            return response()->json(['data' => $result], 422);
        }

        return response()->json(['data' => $result]);
    }
}
