<?php

namespace Agentic\Support;

final class WidgetEmbedAssets
{
    public static function distPath(): string
    {
        return dirname(__DIR__, 2).'/resources/dist/widget';
    }

    /**
     * @return array{js: string, css: string|null}
     */
    public static function resolve(): array
    {
        $base = rtrim((string) (config('agentic.widget.embed.script_url') ?: asset('vendor/agentic/widget')), '/');
        $js = $base.'/agentic-widget.js';
        $cssPath = self::distPath().'/agentic-widget.css';
        $css = is_readable($cssPath) ? $base.'/agentic-widget.css' : null;

        return ['js' => $js, 'css' => $css];
    }
}
