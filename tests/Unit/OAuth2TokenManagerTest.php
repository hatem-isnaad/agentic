<?php

namespace Agentic\Tests\Unit;

use Agentic\Connections\OAuth2TokenManager;
use Agentic\Models\Connection;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class OAuth2TokenManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_credentials_grant_refreshes_and_persists_tokens(): void
    {
        Cache::flush();

        Http::fake([
            'https://auth.example.test/oauth/token' => Http::response([
                'access_token' => 'fresh-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $connection = Connection::create([
            'name' => 'OAuth API',
            'slug' => 'oauth-api',
            'type' => 'oauth2',
            'status' => 'active',
            'config' => [
                'type' => 'oauth2',
                'grant_type' => 'client_credentials',
                'token_url' => 'https://auth.example.test/oauth/token',
            ],
            'credentials' => [
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
            ],
        ]);

        $token = app(OAuth2TokenManager::class)->accessToken($connection->fresh());

        $this->assertSame('fresh-access-token', $token);
        $this->assertSame(
            'fresh-access-token',
            $connection->fresh()->credentials['access_token'],
        );

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://auth.example.test/oauth/token'
                && $request['grant_type'] === 'client_credentials'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret';
        });
    }

    public function test_oauth2_token_endpoint_must_pass_url_validation(): void
    {
        $connection = Connection::create([
            'name' => 'OAuth API',
            'slug' => 'oauth-api-private',
            'type' => 'oauth2',
            'status' => 'active',
            'config' => [
                'type' => 'oauth2',
                'grant_type' => 'client_credentials',
                'token_url' => 'http://127.0.0.1/oauth/token',
            ],
            'credentials' => [
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(OAuth2TokenManager::class)->accessToken($connection);
    }
}
