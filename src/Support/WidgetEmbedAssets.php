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
        $publicRoot = function_exists('public_path') ? public_path('vendor/agentic/widget') : null;
        $jsVersion = is_string($publicRoot) && is_readable($publicRoot.'/agentic-widget.js')
            ? (string) filemtime($publicRoot.'/agentic-widget.js')
            : '1';
        $cssVersion = is_string($publicRoot) && is_readable($publicRoot.'/agentic-widget.css')
            ? (string) filemtime($publicRoot.'/agentic-widget.css')
            : $jsVersion;
        $js = $base.'/agentic-widget.js?v='.$jsVersion;
        $cssPath = self::distPath().'/agentic-widget.css';
        $css = is_readable($cssPath) ? $base.'/agentic-widget.css?v='.$cssVersion : null;

        return ['js' => $js, 'css' => $css];
    }
}
