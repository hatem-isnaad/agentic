<?php

namespace Agentic\Widget\Services;

use Agentic\Agent\AgentDefinition;
use Agentic\Agent\AgentPersona;
use Agentic\Agent\AgentResolver;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Models\WidgetEmbedToken;
use Agentic\Widget\Support\WidgetReplyDelivery;

final class WidgetConfigService
{
    public function __construct(
        private AgentResolver $agents,
        private WidgetSettingsService $settings,
    ) {}

    /**
     * Minimal payload for embed / chat UI (no admin secrets, no AI provider catalog).
     *
     * @return array{data: array<string, mixed>}
     */
    public function forAgent(string $agentSlug, ?WidgetEmbedToken $embed = null): array
    {
        try {
            $agent = $this->agents->resolve($agentSlug);
        } catch (AgentNotFoundException $exception) {
            throw $exception;
        }

        $merged = $this->settings->mergedForAgent($agentSlug);

        return [
            'data' => $this->clientPayload($agent, $merged, $embed),
        ];
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return array<string, mixed>
     */
    private function clientPayload(AgentDefinition $agent, array $merged, ?WidgetEmbedToken $embed): array
    {
        $payload = [
            'agent' => [
                'slug' => $agent->slug ?? $agent->identifier(),
                'name' => AgentPersona::fromAgent($agent)->displayName ?? $agent->name,
            ],
            'theme' => $this->themeForClient($merged),
            'realtime' => $this->realtimeForClient(),
            'reply' => [
                'mode' => WidgetReplyDelivery::isAsync() ? 'async' : 'sync',
            ],
            'history' => [
                'page_size' => (int) config('agentic.widget.history.page_size', 20),
                'max_page_size' => (int) config('agentic.widget.history.max_page_size', 50),
            ],
            'conversation' => [
                'resume_after_hours' => max(1, (int) config('agentic.widget.conversation.resume_after_hours', 24)),
            ],
            'embed' => $this->embedPolicyForClient($embed),
        ];

        $locale = $merged['locale'] ?? config('agentic.widget.locale.default', 'en');
        if (is_string($locale) && $locale !== '') {
            $payload['locale'] = $locale;
        }

        $welcome = $merged['welcome_message'] ?? config('agentic.widget.intake.welcome_message');
        if (is_string($welcome) && trim($welcome) !== '') {
            $payload['welcome'] = $welcome;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function embedPolicyForClient(?WidgetEmbedToken $embed): array
    {
        if ($embed instanceof WidgetEmbedToken) {
            return [
                'require_token' => true,
                'guest_allowed' => $embed->guest_allowed,
                'auth_required' => ! $embed->guest_allowed,
                'sanctum_allowed' => $embed->sanctum_allowed,
            ];
        }

        return [
            'require_token' => filter_var(config('agentic.widget.embed.require_token', true), FILTER_VALIDATE_BOOLEAN),
            'guest_allowed' => filter_var(config('agentic.widget.auth.allow_guest', true), FILTER_VALIDATE_BOOLEAN),
            'auth_required' => false,
            'sanctum_allowed' => filter_var(config('agentic.widget.embed.sanctum_allowed', true), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return array<string, mixed>
     */
    private function themeForClient(array $merged): array
    {
        $theme = is_array($merged['theme'] ?? null) ? $merged['theme'] : [];
        $global = config('agentic.widget.theme', []);

        $mode = $theme['default'] ?? $global['default'] ?? 'system';
        $custom = $theme['custom'] ?? $global['custom'] ?? [];

        $out = ['mode' => $mode];

        if (is_array($custom) && $custom !== []) {
            $out['custom'] = $custom;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function realtimeForClient(): array
    {
        $broadcast = config('agentic.widget.broadcast', []);
        $driver = (string) ($broadcast['driver'] ?? 'polling');
        $prefix = (string) ($broadcast['channel_prefix'] ?? 'agentic-widget');

        if ($driver === 'null' || $driver === '') {
            return ['driver' => 'null'];
        }

        if ($driver === 'pusher') {
            $pusher = $broadcast['pusher'] ?? [];

            return [
                'driver' => 'pusher',
                'channel_prefix' => $prefix,
                'pusher' => array_filter([
                    'key' => $pusher['key'] ?? null,
                    'cluster' => $pusher['cluster'] ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
            ];
        }

        if ($driver === 'socketio') {
            $socket = $broadcast['socketio'] ?? [];

            return array_filter([
                'driver' => 'socketio',
                'channel_prefix' => $prefix,
                'url' => $socket['url'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        $polling = $broadcast['polling'] ?? [];

        return [
            'driver' => 'polling',
            'channel_prefix' => $prefix,
            'interval_ms' => (int) ($polling['interval_ms'] ?? 3000),
        ];
    }
}
