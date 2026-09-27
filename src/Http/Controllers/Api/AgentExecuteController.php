<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Agent\AgentResolver;
use Agentic\Context\RuntimeContext;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Runtime\AgentRuntime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentExecuteController
{
    public function __construct(
        private AgentResolver $agents,
        private AgentRuntime $runtime,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        try {
            $agent = $this->agents->resolve($slug);
        } catch (AgentNotFoundException) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
            'conversation_id' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
        ]);

        $result = $this->runtime->run(
            $agent,
            new AgentExecutionContext(
                message: $validated['message'],
                metadata: $validated['metadata'] ?? [],
                variables: $validated['variables'] ?? [],
                runtime: new RuntimeContext(['request' => $request]),
                conversationId: $validated['conversation_id'] ?? null,
            ),
        );

        if (! $result->success) {
            return response()->json([
                'message' => $result->error ?? 'Agent execution failed.',
            ], 422);
        }

        return response()->json(['data' => $result->output]);
    }
}
