<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Widget\Services\WidgetConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConversationController
{
    public function __construct(
        private WidgetConversationService $conversations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agent = (string) $request->query('agent', '');

        if ($agent === '') {
            return response()->json(['message' => 'Query parameter [agent] is required.'], 422);
        }

        return response()->json([
            'data' => $this->conversations->listForAgent(
                $agent,
                $request->header('X-Agentic-Guest-Id'),
                $request->user()?->getAuthIdentifier(),
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent' => ['required', 'string'],
            'locale' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $conversation = $this->conversations->create(
            $validated['agent'],
            $request->header('X-Agentic-Guest-Id'),
            $request->user()?->getAuthIdentifier(),
            $request->header('X-Agentic-Tenant-Id'),
            $validated['metadata'] ?? [],
        );

        return response()->json(['data' => $conversation], 201);
    }

    public function messages(string $id): JsonResponse
    {
        return response()->json(['data' => $this->conversations->messages($id)]);
    }
}
