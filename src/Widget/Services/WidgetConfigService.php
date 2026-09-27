<?php

namespace Agentic\Widget\Services;

use Agentic\Agent\AgentResolver;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Http\Support\AdminLocaleMeta;

final class WidgetConfigService
{
    public function __construct(
        private AgentResolver $agents,
        private WidgetSettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forAgent(string $agentSlug): array
    {
        try {
            $this->agents->resolve($agentSlug);
        } catch (AgentNotFoundException $exception) {
            throw $exception;
        }

        $merged = $this->settings->mergedForAgent($agentSlug);
        $broadcast = config('agentic.widget.broadcast', []);

        return [
            'data' => [
                'agent' => $agentSlug,
                'auth' => [
                    'mode' => $merged['auth_mode'] ?? config('agentic.widget.auth.mode', 'both'),
                ],
                'conversation' => config('agentic.widget.conversation', []),
                'intake' => [
                    'enabled' => (bool) ($merged['intake_enabled'] ?? false),
                    'welcome_message' => $merged['welcome_message'] ?? null,
                    'questions' => $merged['intake_questions'] ?? [],
                ],
                'locale' => [
                    'default' => $merged['locale'] ?? config('agentic.widget.locale.default', 'en'),
                    'supported' => config('agentic.widget.locale.supported', ['en', 'ar']),
                    'agent_language' => $merged['agent_language'] ?? null,
                ],
                'theme' => $merged['theme'] ?? config('agentic.widget.theme', []),
                'reply' => [
                    'formats' => $merged['reply_formats'] ?? ['blocks'],
                    'render' => 'blocks',
                ],
                'realtime' => [
                    'driver' => $broadcast['driver'] ?? 'polling',
                    'channel_prefix' => $broadcast['channel_prefix'] ?? 'agentic-widget',
                    'polling' => $broadcast['polling'] ?? ['interval_ms' => 3000],
                    'pusher' => [
                        'key' => $broadcast['pusher']['key'] ?? null,
                        'cluster' => $broadcast['pusher']['cluster'] ?? null,
                    ],
                    'socketio' => $broadcast['socketio'] ?? [],
                ],
                'providers' => config('agentic.ai.providers', []),
            ],
            'meta' => AdminLocaleMeta::build(),
        ];
    }
}
