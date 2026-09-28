<?php

namespace Agentic\Connections;

use Agentic\Models\Connection;
use InvalidArgumentException;

final class ConnectionService
{
    /** @return list<string> */
    public static function types(): array
    {
        return ['bearer', 'basic', 'header', 'query', 'oauth2'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Connection
    {
        return Connection::query()->create($this->normalize($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Connection $connection, array $data): Connection
    {
        $connection->fill($this->normalize($data, $connection));
        $connection->save();

        return $connection->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(Connection $connection): array
    {
        $credentials = is_array($connection->credentials) ? $connection->credentials : [];
        $config = is_array($connection->config) ? $connection->config : [];

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'slug' => $connection->slug,
            'type' => $connection->type,
            'status' => $connection->status,
            'config' => [
                'type' => $config['type'] ?? $connection->type,
                'grant_type' => $config['grant_type'] ?? null,
                'token_url' => $config['token_url'] ?? null,
                'scope' => $config['scope'] ?? null,
                'expiry_skew' => $config['expiry_skew'] ?? 60,
                'timeout' => $config['timeout'] ?? 15,
                'name' => $config['name'] ?? null,
                'headers' => ConnectionRequestExtras::normalizeMap($config['headers'] ?? []),
                'query' => ConnectionRequestExtras::normalizeMap($config['query'] ?? []),
                'auth_headers' => ConnectionRequestExtras::normalizeMap($config['auth_headers'] ?? []),
                'auth_query' => ConnectionRequestExtras::normalizeMap($config['auth_query'] ?? []),
                'auth_body' => ConnectionRequestExtras::normalizeMap($config['auth_body'] ?? []),
            ],
            'has_credentials' => $connection->getRawOriginal('credentials') !== null,
            'credential_keys' => array_values(array_filter(array_keys($credentials), fn (string $key): bool => $credentials[$key] !== null && $credentials[$key] !== '')),
            'expires_at' => isset($credentials['expires_at']) ? (int) $credentials['expires_at'] : null,
            'created_at' => optional($connection->created_at)?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, ?Connection $existing = null): array
    {
        $type = strtolower((string) ($data['type'] ?? $existing?->type ?? 'bearer'));
        if (! in_array($type, self::types(), true)) {
            throw new InvalidArgumentException('Unsupported connection type ['.$type.'].');
        }

        $config = array_merge(
            is_array($existing?->config) ? $existing->config : [],
            is_array($data['config'] ?? null) ? $data['config'] : [],
        );
        $credentials = is_array($existing?->credentials) ? $existing->credentials : [];
        $incoming = is_array($data['credentials'] ?? null) ? $data['credentials'] : [];
        foreach ($incoming as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $credentials[$key] = $value;
        }

        $config['type'] = $type;
        foreach (['grant_type', 'token_url', 'scope', 'expiry_skew', 'timeout'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                $config[$key] = $data[$key];
            }
        }

        if (isset($data['name_key']) && is_string($data['name_key']) && $data['name_key'] !== '') {
            $config['name'] = $data['name_key'];
        }

        foreach (['headers', 'query', 'auth_headers', 'auth_query', 'auth_body'] as $key) {
            if (array_key_exists($key, $data)) {
                $config[$key] = ConnectionRequestExtras::normalizeMap($data[$key]);
            } elseif (is_array($data['config'] ?? null) && array_key_exists($key, $data['config'])) {
                $config[$key] = ConnectionRequestExtras::normalizeMap($data['config'][$key]);
            }
        }

        foreach (['token', 'username', 'password', 'value', 'client_id', 'client_secret', 'refresh_token', 'access_token'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                $credentials[$key] = $data[$key];
            }
        }

        if ($type === 'oauth2' && ! isset($config['grant_type'])) {
            $config['grant_type'] = 'refresh_token';
        }

        return [
            'name' => (string) ($data['name'] ?? $existing?->name ?? ''),
            'slug' => (string) ($data['slug'] ?? $existing?->slug ?? ''),
            'type' => $type,
            'status' => (string) ($data['status'] ?? $existing?->status ?? 'active'),
            'config' => $config,
            'credentials' => $credentials,
        ];
    }
}
