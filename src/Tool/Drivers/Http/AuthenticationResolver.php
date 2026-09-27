<?php

namespace Agentic\Tool\Drivers\Http;

use Agentic\Contracts\Connections\ConnectionResolver;
use Agentic\Connections\OAuth2TokenManager;
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

            $auth = array_merge($model->config ?? [], $credentials);
        }

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

    private function secret(array $auth, string $key): string
    {
        $value = $auth[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("HTTP auth configuration is missing [{$key}].");
        }

        if (str_starts_with($value, 'env:')) {
            $env = getenv(substr($value, 4));

            if ($env === false || $env === '') {
                throw new InvalidArgumentException("Environment secret [{$value}] is not set.");
            }

            return $env;
        }

        return $value;
    }
}
