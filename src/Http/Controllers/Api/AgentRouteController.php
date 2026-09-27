<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Routing\AgentRouter;
use Agentic\Routing\RoutingContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentRouteController
{
    public function __construct(
        private AgentRouter $router,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'agent' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->router->route(new RoutingContext(
                message: $validated['message'],
                agentHint: $validated['agent'] ?? null,
                attributes: $validated['metadata'] ?? [],
            ));
        } catch (AgentNotFoundException) {
            return response()->json(['message' => 'No agent matched the request.'], 404);
        }

        return response()->json([
            'data' => [
                'agent' => $result->agent,
                'strategy' => $result->strategy,
                'confidence' => $result->confidence,
            ],
        ]);
    }
}
