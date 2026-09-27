<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Contracts\Repositories\ConversationRepository;
use Illuminate\Http\JsonResponse;

final class ConversationController
{
    public function __construct(
        private ConversationRepository $conversations,
    ) {}

    public function show(string $id): JsonResponse
    {
        $conversation = $this->conversations->find($id);

        if ($conversation === null) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'agent' => $conversation->agent,
                'user_id' => $conversation->userId,
                'tenant_id' => $conversation->tenantId,
                'sdk_conversation_id' => $conversation->sdkConversationId,
                'metadata' => $conversation->metadata,
            ],
        ]);
    }
}
