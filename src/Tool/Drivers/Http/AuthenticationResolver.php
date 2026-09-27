<?php

namespace Agentic\Tool\Drivers\Http;

use InvalidArgumentException;

/**
 * Resolves HTTP authentication into headers / query parameters.
 *
 * Secrets should be supplied via configuration / env — never from the LLM.
 */
final class AuthenticationResolver
{
    /**
     * @param  array<string, mixed>|null  $auth
     * @return array{headers: array<string, string>, query: array<string, string>}
     */
    public function resolve(?array $auth): array
    {
        if ($auth === null || $auth === []) {
            return ['headers' => [], 'query' => []];
        }

        $type = strtolower((string) ($auth['type'] ?? 'none'));

        return match ($type) {
            'none', '' => ['headers' => [], 'query' => []],
            'bearer' => [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->requireSecret($auth, 'token'),
                ],
                'query' => [],
            ],
            'basic' => [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode(
                        $this->requireSecret($auth, 'username').':'.$this->requireSecret($auth, 'password')
                    ),
                ],
                'query' => [],
            ],
            'header' => [
                'headers' => [
                    (string) ($auth['name'] ?? 'X-Api-Key') => $this->requireSecret($auth, 'value'),
                ],
                'query' => [],
            ],
            'query' => [
                'headers' => [],
                'query' => [
                    (string) ($auth['name'] ?? 'api_key') => $this->requireSecret($auth, 'value'),
                ],
            ],
            default => throw new InvalidArgumentException("Unsupported HTTP auth type [{$type}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $auth
     */
    private function requireSecret(array $auth, string $key): string
    {
        $value = $auth[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("HTTP auth configuration is missing [{$key}].");
        }

        // Allow env:SECRET_NAME indirection so secrets are not baked into tool JSON.
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
