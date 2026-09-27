<?php

namespace Agentic\Connections;

use Agentic\Models\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class OAuth2TokenManager
{
    public function accessToken(Connection $connection): string
    {
        $credentials = is_array($connection->credentials) ? $connection->credentials : [];
        $accessToken = $credentials['access_token'] ?? null;
        $expiresAt = isset($credentials['expires_at']) ? (int) $credentials['expires_at'] : null;
        $skew = max(0, (int) ($connection->config['expiry_skew'] ?? 60));

        if (is_string($accessToken) && $accessToken !== '' && ($expiresAt === null || $expiresAt > time() + $skew)) {
            return $accessToken;
        }

        return $this->refresh($connection);
    }

    public function refresh(Connection $connection): string
    {
        $lock = Cache::lock('agentic:oauth2:refresh:'.$connection->getKey(), 30);

        return $lock->block(10, function () use ($connection): string {
            $fresh = $connection->fresh();

            if (! $fresh instanceof Connection) {
                throw new RuntimeException('OAuth2 connection no longer exists.');
            }

            $credentials = is_array($fresh->credentials) ? $fresh->credentials : [];
            $skew = max(0, (int) ($fresh->config['expiry_skew'] ?? 60));
            $accessToken = $credentials['access_token'] ?? null;
            $expiresAt = isset($credentials['expires_at']) ? (int) $credentials['expires_at'] : null;

            if (is_string($accessToken) && $accessToken !== '' && ($expiresAt === null || $expiresAt > time() + $skew)) {
                return $accessToken;
            }

            $config = is_array($fresh->config) ? $fresh->config : [];
            $tokenUrl = $config['token_url'] ?? null;

            if (! is_string($tokenUrl) || $tokenUrl === '') {
                throw new InvalidArgumentException('OAuth2 connection is missing [token_url].');
            }

            $grantType = (string) ($config['grant_type'] ?? 'refresh_token');
            if (! in_array($grantType, ['refresh_token', 'client_credentials'], true)) {
                throw new InvalidArgumentException("Unsupported OAuth2 grant type [{$grantType}].");
            }

            $clientId = $credentials['client_id'] ?? null;
            $clientSecret = $credentials['client_secret'] ?? null;

            if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
                throw new InvalidArgumentException('OAuth2 connection is missing client credentials.');
            }

            $form = [
                'grant_type' => $grantType,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ];

            if ($grantType === 'refresh_token') {
                $refreshToken = $credentials['refresh_token'] ?? null;

                if (! is_string($refreshToken) || $refreshToken === '') {
                    throw new InvalidArgumentException('OAuth2 connection is missing [refresh_token].');
                }

                $form['refresh_token'] = $refreshToken;
            }

            if (isset($config['scope']) && is_string($config['scope']) && $config['scope'] !== '') {
                $form['scope'] = $config['scope'];
            }

            $response = Http::asForm()
                ->acceptJson()
                ->timeout((float) ($config['timeout'] ?? 15))
                ->post($tokenUrl, $form);

            if ($response->failed()) {
                throw new RuntimeException('OAuth2 token endpoint returned HTTP '.$response->status().'.');
            }

            $payload = $response->json();

            if (! is_array($payload) || ! is_string($payload['access_token'] ?? null) || $payload['access_token'] === '') {
                throw new RuntimeException('OAuth2 token endpoint did not return a valid access token.');
            }

            $credentials['access_token'] = $payload['access_token'];

            if (isset($payload['refresh_token']) && is_string($payload['refresh_token']) && $payload['refresh_token'] !== '') {
                $credentials['refresh_token'] = $payload['refresh_token'];
            }

            if (isset($payload['expires_in']) && is_numeric($payload['expires_in'])) {
                $credentials['expires_at'] = time() + max(0, (int) $payload['expires_in']);
            } else {
                unset($credentials['expires_at']);
            }

            $fresh->credentials = $credentials;
            $fresh->save();

            return $credentials['access_token'];
        });
    }
}
