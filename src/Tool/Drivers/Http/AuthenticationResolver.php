<?php

namespace Agentic\Tool\Drivers\Http;

use Agentic\Connections\ConnectionRequestExtras;
use Agentic\Connections\OAuth2TokenManager;
use Agentic\Contracts\Connections\ConnectionResolver;
use Agentic\Models\Connection;
use InvalidArgumentException;

final class AuthenticationResolver
{
    public function __construct(
        private ?ConnectionResolver $connections = null,
        private ?OAuth2TokenManager $oauth2 = null,
    ) {}

    public function resolve(?array $auth, ?string $connection = null): array
    {
        if ($connection !== null) {
            if ($this->connections === null) {
                throw new InvalidArgumentException('HTTP connection resolver is not configured.');
            }

            $model = $this->connections->resolve($connection);

            if ($model === null || $model->status !== 'active') {
                throw new InvalidArgumentException("HTTP connection [{$connection}] is unavailable.");
            }

            $credentials = $model->credentials;

            if (! is_array($credentials)) {
                throw new InvalidArgumentException("HTTP connection [{$connection}] has no credentials.");
            }

            if ($this->usesOAuth2($model)) {
                if ($this->oauth2 === null) {
                    throw new InvalidArgumentException('OAuth2 token manager is not configured.');
                }

                return $this->withConnectionExtras($model, [
                    'headers' => ['Authorization' => 'Bearer '.$this->oauth2->accessToken($model)],
                    'query' => [],
                ]);
            }

            $auth = array_merge($model->config ?? [], $credentials);

            return $this->withConnectionExtras($model, $this->resolveAuth($auth));
        }

        return $this->resolveAuth($auth);
    }

    /**
     * @param  array<string, mixed>|null  $auth
     * @return array{headers: array<string, string>, query: array<string, string>}
     */
    private function resolveAuth(?array $auth): array
    {
        if ($auth === null || $auth === []) {
            return ['headers' => [], 'query' => []];
        }

        $type = strtolower((string) ($auth['type'] ?? 'none'));

        return match ($type) {
            'none', '' => ['headers' => [], 'query' => []],
            'bearer' => ['headers' => ['Authorization' => 'Bearer '.$this->secret($auth, 'token')], 'query' => []],
            'basic' => ['headers' => ['Authorization' => 'Basic '.base64_encode($this->secret($auth, 'username').':'.$this->secret($auth, 'password'))], 'query' => []],
            'header' => ['headers' => [(string) ($auth['name'] ?? 'X-Api-Key') => $this->secret($auth, 'value')], 'query' => []],
            'query' => ['headers' => [], 'query' => [(string) ($auth['name'] ?? 'api_key') => $this->secret($auth, 'value')]],
            default => throw new InvalidArgumentException("Unsupported HTTP auth type [{$type}]."),
        };
    }

    /**
     * @param  array{headers: array<string, string>, query: array<string, string>}  $auth
     * @return array{headers: array<string, string>, query: array<string, string>}
     */
    private function withConnectionExtras(Connection $connection, array $auth): array
    {
        $extras = ConnectionRequestExtras::forRequests(is_array($connection->config) ? $connection->config : []);

        return [
            'headers' => array_merge($extras['headers'], $auth['headers']),
            'query' => array_merge($extras['query'], $auth['query']),
        ];
    }

    private function secret(array $auth, string $key): string
    {
        $value = $auth[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("HTTP auth configuration is missing [{$key}].");
        }

        if (str_starts_with($value, 'env:')) {
            $key = substr($value, 4);
            $env = getenv($key);
            if ($env === false || $env === '') {
                $env = (string) env($key, '');
            }
            if ($env === '') {
                throw new InvalidArgumentException("Environment secret [{$value}] is not set.");
            }

            return $env;
        }

        return $value;
    }

    private function usesOAuth2(Connection $connection): bool
    {
        if (strtolower((string) $connection->type) === 'oauth2') {
            return true;
        }

        $config = is_array($connection->config) ? $connection->config : [];

        return strtolower((string) ($config['type'] ?? '')) === 'oauth2';
    }
}
