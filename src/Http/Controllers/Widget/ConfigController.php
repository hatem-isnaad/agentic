<?php

namespace Agentic\Http\Controllers\Widget;

use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Http\Requests\Widget\WidgetAgentQueryRequest;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Models\WidgetEmbedToken;
use Agentic\Widget\Services\WidgetConfigService;
use Illuminate\Http\JsonResponse;

final class ConfigController
{
    public function __construct(
        private WidgetConfigService $config,
    ) {}

    public function __invoke(WidgetAgentQueryRequest $request): JsonResponse
    {
        try {
            $embed = $request->attributes->get('agentic_widget_embed');

            return response()->json($this->config->forAgent(
                $request->agentSlug(),
                $embed instanceof WidgetEmbedToken ? $embed : null,
            ));
        } catch (AgentNotFoundException) {
            return JsonApiResponse::error('Agent not found.', 404);
        }
    }
}
