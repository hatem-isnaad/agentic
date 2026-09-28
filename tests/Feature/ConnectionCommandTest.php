<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Connection;
use Agentic\Models\Tool;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

final class ConnectionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_create_without_prompts_when_flags_set(): void
    {
        $this->artisan('agentic:connection', [
            'action' => 'create',
            '--name' => 'Limenos',
            '--slug' => 'limenos-merchant',
            '--type' => 'bearer',
            '--token' => 'lim_test_token',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Connection::query()->where('slug', 'limenos-merchant')->count());
    }

    public function test_connection_create_stores_cli_header_and_query_flags(): void
    {
        $this->artisan('agentic:connection', [
            'action' => 'create',
            '--name' => 'Limenos',
            '--slug' => 'limenos-headers',
            '--type' => 'bearer',
            '--token' => 'lim_test_token',
            '--header' => ['X-Store-Id:store-1', 'Accept:application/json'],
            '--query' => ['locale=ar'],
            '--no-interaction' => true,
        ])->assertSuccessful();

        $row = Connection::query()->where('slug', 'limenos-headers')->firstOrFail();
        $this->assertSame('store-1', $row->config['headers']['X-Store-Id']);
        $this->assertSame('ar', $row->config['query']['locale']);
    }

    public function test_http_tool_create_without_prompts_when_flags_set(): void
    {
        Connection::query()->create([
            'name' => 'Limenos',
            'slug' => 'limenos-merchant',
            'type' => 'bearer',
            'status' => 'active',
            'credentials' => ['token' => 'lim_test_token'],
        ]);

        $this->artisan('agentic:http-tool', [
            'action' => 'create',
            '--name' => 'Get order',
            '--slug' => 'limenos-orders-get',
            '--method' => 'GET',
            '--url' => 'https://limenos.ai/api/v1/merchant/orders/{reference}',
            '--connection' => 'limenos-merchant',
            '--param' => 'reference',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Tool::query()->where('slug', 'limenos-orders-get')->count());
    }

    public function test_http_tool_test_sends_request_from_cli_flags(): void
    {
        Http::fake([
            'https://api.example.test/orders/99' => Http::response(['ok' => true], 200),
        ]);

        $this->artisan('agentic:http-tool', [
            'action' => 'create',
            '--name' => 'Get order',
            '--slug' => 'orders-get',
            '--method' => 'GET',
            '--url' => 'https://api.example.test/orders/{id}',
            '--param' => 'id',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->artisan('agentic:http-tool', [
            'action' => 'test',
            'slug' => 'orders-get',
            '--arg' => ['id=99'],
            '--no-interaction' => true,
        ])->assertSuccessful();
    }

    public function test_http_tool_clone_from_cli_flags(): void
    {
        $this->artisan('agentic:http-tool', [
            'action' => 'create',
            '--name' => 'Get order',
            '--slug' => 'orders-get',
            '--method' => 'GET',
            '--url' => 'https://api.example.test/orders/{id}',
            '--param' => 'id',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->artisan('agentic:http-tool', [
            'action' => 'clone',
            'slug' => 'orders-get',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertTrue(Tool::query()->where('slug', 'orders-get-copy')->exists());
    }
}
