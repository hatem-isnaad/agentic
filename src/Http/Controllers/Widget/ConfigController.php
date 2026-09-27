<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Widget\Services\WidgetConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConfigController
{
    public function __construct(
        private WidgetConfigService $config,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $agent = (string) $request->query('agent', '');

        if ($agent === '') {
            return response()->json(['message' => 'Query parameter [agent] is required.'], 422);
        }

        try {
            return response()->json($this->config->forAgent($agent));
        } catch (AgentNotFoundException) {
            return response()->json(['message' => 'Agent not found.'], 404);
        }
    }
}
