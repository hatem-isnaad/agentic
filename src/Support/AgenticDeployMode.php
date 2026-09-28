<?php

namespace Agentic\Support;

/**
 * One switch for routes + auth defaults. Individual AGENTIC_* toggles still exist in config
 * but this applies sensible presets so hosts are not lost in env vars.
 *
 * AGENTIC_MODE=local       — laptop: admin + widget demo, no Sanctum on admin API
 * AGENTIC_MODE=production — live full stack: admin + widget API, Sanctum + embed token
 * AGENTIC_MODE=widget      — live embed only: widget JSON API + embed token (no admin)
 */
final class AgenticDeployMode
{
    public const Local = 'local';

    public const Production = 'production';

    public const Widget = 'widget';

    public static function current(): string
    {
        if (self::legacyWidgetOnly()) {
            return self::Widget;
        }

        $mode = strtolower(trim((string) config('agentic.deploy.mode', '')));

        if ($mode === self::Widget) {
            return self::Widget;
        }

        if ($mode === self::Production) {
            return self::Production;
        }

        if ($mode === self::Local) {
            return self::Local;
        }

        return app()->environment(['local', 'testing']) ? self::Local : self::Production;
    }

    public static function isWidgetOnly(): bool
    {
        return self::current() === self::Widget;
    }

    public static function applyPresets(): void
    {
        match (self::current()) {
            self::Widget => self::applyWidget(),
            self::Production => self::applyProduction(),
            default => self::applyLocal(),
        };
    }

    private static function legacyWidgetOnly(): bool
    {
        return filter_var(env('AGENTIC_WIDGET_ONLY', false), FILTER_VALIDATE_BOOLEAN);
    }

    private static function applyLocal(): void
    {
        config([
            'agentic.admin.enabled' => true,
            'agentic.admin.api.enabled' => true,
            'agentic.admin.web.enabled' => true,
            'agentic.widget.enabled' => true,
            'agentic.widget.web.enabled' => true,
            'agentic.api.enabled' => filter_var(env('AGENTIC_API_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'agentic.channels.enabled' => true,
            'agentic.auth.enabled' => true,
            'agentic.auth.protect.admin_api' => false,
            'agentic.auth.protect.runtime_api' => false,
            'agentic.widget.embed.require_token' => filter_var(
                env('AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN', false),
                FILTER_VALIDATE_BOOLEAN,
            ),
        ]);
    }

    private static function applyProduction(): void
    {
        config([
            'agentic.admin.enabled' => true,
            'agentic.admin.api.enabled' => true,
            'agentic.admin.web.enabled' => filter_var(
                env('AGENTIC_ADMIN_WEB_ENABLED', true),
                FILTER_VALIDATE_BOOLEAN,
            ),
            'agentic.widget.enabled' => true,
            'agentic.widget.web.enabled' => filter_var(
                env('AGENTIC_WIDGET_WEB_ENABLED', false),
                FILTER_VALIDATE_BOOLEAN,
            ),
            'agentic.api.enabled' => filter_var(env('AGENTIC_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'agentic.channels.enabled' => filter_var(env('AGENTIC_CHANNELS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'agentic.auth.enabled' => true,
            'agentic.auth.protect.admin_api' => true,
            'agentic.auth.protect.runtime_api' => true,
            'agentic.widget.embed.require_token' => true,
        ]);
    }

    private static function applyWidget(): void
    {
        config([
            'agentic.admin.enabled' => false,
            'agentic.admin.api.enabled' => false,
            'agentic.admin.web.enabled' => false,
            'agentic.api.enabled' => false,
            'agentic.channels.enabled' => false,
            'agentic.auth.enabled' => false,
            'agentic.widget.enabled' => true,
            'agentic.widget.web.enabled' => false,
            'agentic.widget.embed.require_token' => true,
            'agentic.filament.panels' => [],
            'agentic.auth.protect.admin_api' => true,
            'agentic.auth.protect.runtime_api' => true,
        ]);
    }
}
