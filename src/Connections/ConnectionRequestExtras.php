<?php

namespace Agentic\Connections;

final class ConnectionRequestExtras
{
    /**
     * @param  array<string, mixed>  $config
     * @return array{headers: array<string, string>, query: array<string, string>}
     */
    public static function forRequests(array $config): array
    {
        return [
            'headers' => self::resolve(self::normalizeMap($config['headers'] ?? [])),
            'query' => self::resolve(self::normalizeMap($config['query'] ?? [])),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{headers: array<string, string>, query: array<string, string>, body: array<string, string>}
     */
    public static function forAuth(array $config): array
    {
        return [
            'headers' => self::resolve(self::normalizeMap($config['auth_headers'] ?? [])),
            'query' => self::resolve(self::normalizeMap($config['auth_query'] ?? [])),
            'body' => self::resolve(self::normalizeMap($config['auth_body'] ?? [])),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function normalizeMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = [];

        foreach ($value as $key => $item) {
            if (is_array($item) && isset($item['key'])) {
                $name = trim((string) $item['key']);
                $raw = $item['value'] ?? '';
            } elseif (is_string($key) && $key !== '') {
                $name = trim($key);
                $raw = $item;
            } else {
                continue;
            }

            if ($name === '' || (! is_string($raw) && ! is_numeric($raw))) {
                continue;
            }

            $map[$name] = (string) $raw;
        }

        return $map;
    }

    /**
     * Parse CLI flags like "X-Store-Id:abc" or "locale=ar".
     *
     * @param  list<string>|string|null  $pairs
     * @return array<string, string>
     */
    public static function fromCliPairs(mixed $pairs): array
    {
        $items = is_array($pairs) ? $pairs : (is_string($pairs) && $pairs !== '' ? [$pairs] : []);
        $map = [];

        foreach ($items as $pair) {
            if (! is_string($pair) || $pair === '') {
                continue;
            }

            $split = preg_split('/[:=]/', $pair, 2);
            if (! is_array($split) || ! isset($split[0]) || trim($split[0]) === '') {
                continue;
            }

            $map[trim($split[0])] = (string) ($split[1] ?? '');
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $map
     * @return array<string, string>
     */
    public static function resolve(array $map): array
    {
        $out = [];

        foreach ($map as $key => $value) {
            $out[$key] = self::resolveValue($value);
        }

        return $out;
    }

    public static function resolveValue(string $value): string
    {
        if (! str_starts_with($value, 'env:')) {
            return $value;
        }

        $key = substr($value, 4);
        $env = getenv($key);
        if ($env === false || $env === '') {
            $env = (string) env($key, '');
        }

        if ($env === '') {
            throw new \InvalidArgumentException("Environment secret [{$value}] is not set.");
        }

        return $env;
    }
}
