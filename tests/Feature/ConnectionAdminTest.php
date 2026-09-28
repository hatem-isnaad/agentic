<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Connection;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ConnectionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_bearer_connection_without_leaking_token(): void
    {
        $this->postJson('/api/agentic/admin/connections', [
            'name' => 'Limenos',
            'slug' => 'limenos-merchant',
            'type' => 'bearer',
            'token' => 'lim_test_secret_token',
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'limenos-merchant')
            ->assertJsonPath('data.has_credentials', true)
            ->assertJsonMissing(['lim_test_secret_token']);

        $this->getJson('/api/agentic/admin/connections')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_create_oauth2_connection(): void
    {
        $this->postJson('/api/agentic/admin/connections', [
            'name' => 'Orders OAuth',
            'slug' => 'orders-oauth',
            'type' => 'oauth2',
            'grant_type' => 'refresh_token',
            'token_url' => 'https://auth.example.test/oauth/token',
            'client_id' => 'id',
            'client_secret' => 'secret',
            'refresh_token' => 'refresh-1',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'oauth2')
            ->assertJsonPath('data.config.token_url', 'https://auth.example.test/oauth/token')
            ->assertJsonMissing(['secret', 'refresh-1']);

        $this->assertSame(1, Connection::query()->count());
    }

    public function test_admin_can_store_extra_headers_and_query_on_a_connection(): void
    {
        $this->postJson('/api/agentic/admin/connections', [
            'name' => 'Limenos',
            'slug' => 'limenos-merchant',
            'type' => 'bearer',
            'token' => 'lim_test_secret_token',
            'headers' => [
                'X-Store-Id' => 'store-1',
                'Accept' => 'application/json',
            ],
            'query' => [
                'locale' => 'ar',
            ],
        ])->assertCreated()
            ->assertJsonPath('data.config.headers.X-Store-Id', 'store-1')
            ->assertJsonPath('data.config.query.locale', 'ar');

        $row = Connection::query()->where('slug', 'limenos-merchant')->firstOrFail();
        $this->assertSame('store-1', $row->config['headers']['X-Store-Id']);
        $this->assertSame('ar', $row->config['query']['locale']);
    }
}
