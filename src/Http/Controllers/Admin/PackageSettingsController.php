<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
use Illuminate\Http\JsonResponse;

final class PackageSettingsController
{
    public function show(): JsonResponse
    {
        $providers = config('agentic.ai.providers', []);

        return response()->json([
            'data' => [
                'features' => [
                    'api' => (bool) config('agentic.api.enabled'),
                    'admin_api' => (bool) config('agentic.admin.api.enabled'),
                    'admin_web' => (bool) config('agentic.admin.web.enabled'),
                    'widget' => (bool) config('agentic.widget.enabled'),
                    'widget_web' => (bool) config('agentic.widget.web.enabled'),
                    'auth' => (bool) config('agentic.auth.enabled'),
                ],
                'ai' => [
                    'default_provider' => config('agentic.ai.default_provider'),
                    'providers' => array_keys(is_array($providers) ? $providers : []),
                ],
                'knowledge' => [
                    'driver' => config('agentic.knowledge.driver'),
                    'embedding' => config('agentic.knowledge.embedding'),
                    'vector_store' => config('agentic.vector_store', config('agentic.knowledge.vector_store')),
                    'queue_reindex' => (bool) config('agentic.knowledge.queue_reindex', false),
                ],
                'memory' => [
                    'driver' => config('agentic.memory.driver'),
                ],
                'conversation' => [
                    'driver' => config('agentic.conversation.driver'),
                ],
                'execution' => [
                    'driver' => config('agentic.execution.driver'),
                ],
                'permission' => [
                    'checker' => config('agentic.permission.checker'),
                ],
                'mcp' => [
                    'tool_prefix' => config('agentic.mcp.tool_prefix', ''),
                ],
                'widget' => [
                    'prefix' => config('agentic.widget.prefix'),
                    'auth_mode' => config('agentic.widget.auth.mode'),
                    'default_locale' => config('agentic.widget.locale.default'),
                ],
                'admin' => [
                    'prefix' => config('agentic.admin.prefix'),
                    'locales' => config('agentic.admin.locales', ['en', 'ar']),
                ],
            ],
            'meta' => AdminLocaleMeta::build(),
        ]);
    }
}
