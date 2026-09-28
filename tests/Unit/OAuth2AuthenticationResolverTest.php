<?php

namespace Agentic\Tests\Unit;

use Agentic\Models\Connection;
use Agentic\Tests\TestCase;
use Agentic\Tool\Drivers\Http\AuthenticationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class OAuth2AuthenticationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_slug_resolves_oauth2_bearer_header(): void
    {
        Cache::flush();

        Http::fake([
            'https://auth.example.test/oauth/token' => Http::response([
                'access_token' => 'oauth-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        Connection::create([
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

        $auth = app(AuthenticationResolver::class)->resolve(null, 'oauth-api');

        $this->assertSame(
            ['Authorization' => 'Bearer oauth-access-token'],
            $auth['headers'],
        );
        $this->assertSame([], $auth['query']);
    }

    public function test_connection_extras_are_merged_onto_every_request(): void
    {
        Connection::create([
            'name' => 'Merchant',
            'slug' => 'merchant',
            'type' => 'bearer',
            'status' => 'active',
            'config' => [
                'type' => 'bearer',
                'headers' => ['X-Store-Id' => 'store-9', 'Accept' => 'application/json'],
                'query' => ['locale' => 'ar'],
            ],
            'credentials' => [
                'token' => 'secret-token',
            ],
        ]);

        $auth = app(AuthenticationResolver::class)->resolve(null, 'merchant');

        $this->assertSame('Bearer secret-token', $auth['headers']['Authorization']);
        $this->assertSame('store-9', $auth['headers']['X-Store-Id']);
        $this->assertSame('application/json', $auth['headers']['Accept']);
        $this->assertSame(['locale' => 'ar'], $auth['query']);
    }
}
