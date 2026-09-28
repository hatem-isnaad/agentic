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

        $publicRoot = function_exists('public_path') ? public_path('vendor/agentic/admin') : null;
        $base = asset('vendor/agentic/admin');
        $scriptPath = ltrim($entry['file'], '/');
        $stylePath = isset($entry['css'][0]) ? ltrim($entry['css'][0], '/') : null;

        $script = $base.'/'.$scriptPath.self::assetVersion($publicRoot, $scriptPath);
        $style = $stylePath !== null
            ? $base.'/'.$stylePath.self::assetVersion($publicRoot, $stylePath)
            : null;

        return ['style' => $style, 'script' => $script, 'dev' => false];
    }

    public static function manifestPath(): string
    {
        $published = function_exists('public_path')
            ? public_path('vendor/agentic/admin/.vite/manifest.json')
            : null;

        if (is_string($published) && is_readable($published)) {
            return $published;
        }

        return dirname(__DIR__, 2).'/resources/dist/admin/.vite/manifest.json';
    }

    public static function distPath(): string
    {
        return dirname(__DIR__, 2).'/resources/dist/admin';
    }

    private static function assetVersion(?string $publicRoot, string $relative): string
    {
        if (! is_string($publicRoot)) {
            return '';
        }

        $absolute = $publicRoot.'/'.$relative;

        return is_readable($absolute) ? '?v='.(string) filemtime($absolute) : '';
    }
}
