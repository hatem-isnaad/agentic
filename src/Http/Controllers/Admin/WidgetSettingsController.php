<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Widget\Services\WidgetSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WidgetSettingsController
{
    public function __construct(
        private WidgetSettingsService $settings,
    ) {}

    public function schema(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->schema(),
            'meta' => AdminLocaleMeta::build(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(AdminPaginator::paginate($this->settings->all(), $request));
    }

    public function show(string $agentSlug): JsonResponse
    {
        return response()->json([
            'data' => [
                'agent_slug' => $agentSlug,
                'settings' => $this->settings->mergedForAgent($agentSlug),
            ],
            'meta' => AdminLocaleMeta::build(),
        ]);
    }

    public function update(Request $request, string $agentSlug): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $this->settings->upsert($agentSlug, $validated['settings']);

        return response()->json([
            'data' => [
                'agent_slug' => $agentSlug,
                'settings' => $this->settings->mergedForAgent($agentSlug),
            ],
            'meta' => AdminLocaleMeta::build(),
        ]);
    }

    public function destroy(string $agentSlug): JsonResponse
    {
        if (! $this->settings->delete($agentSlug)) {
            return response()->json(['message' => 'Widget settings not found.'], 404);
        }

        return response()->json(null, 204);
    }
}
