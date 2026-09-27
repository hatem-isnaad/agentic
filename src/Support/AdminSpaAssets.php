<?php

namespace Agentic\Support;

final class AdminSpaAssets
{
    /**
     * @return array{style: ?string, script: ?string, dev: bool}
     */
    public static function resolve(): array
    {
        $manifestPath = self::manifestPath();

        if (! is_readable($manifestPath)) {
            $legacy = self::distPath().'/manifest.json';
            if (! is_readable($legacy)) {
                return ['style' => null, 'script' => null, 'dev' => true];
            }
            $manifestPath = $legacy;
        }

        /** @var array<string, array{file: string, css?: list<string>}> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $entry = $manifest['resources/js/admin/main.tsx'] ?? reset($manifest);

        if (! is_array($entry)) {
            return ['style' => null, 'script' => null, 'dev' => true];
        }

        $base = asset('vendor/agentic/admin');
        $script = $base.'/'.ltrim($entry['file'], '/');
        $style = isset($entry['css'][0]) ? $base.'/'.ltrim($entry['css'][0], '/') : null;

        return ['style' => $style, 'script' => $script, 'dev' => false];
    }

    public static function manifestPath(): string
    {
        return dirname(__DIR__, 2).'/resources/dist/admin/.vite/manifest.json';
    }

    public static function distPath(): string
    {
        return dirname(__DIR__, 2).'/resources/dist/admin';
    }
}
