<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

final class HttpToolAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_http_query_and_nested_body_on_the_definition(): void
    {
        $this->postJson('/api/agentic/admin/tools', [
            'name' => 'Create order',
            'slug' => 'limenos-orders-create',
            'driver' => 'http',
            'status' => 'published',
            'publish' => true,
            'definition' => [
                'method' => 'POST',
                'url' => 'https://limenos.ai/api/v1/merchant/orders',
                'connection' => 'limenos-merchant',
                'timeout' => 20,
                'query' => ['status' => '{status}'],
                'headers' => ['Accept' => 'application/json'],
                'body' => [
                    'order' => [
                        'reference' => '{reference}',
                    ],
                ],
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'reference' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                    ],
                    'required' => ['reference'],
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.definition.method', 'POST')
            ->assertJsonPath('data.definition.query.status', '{status}')
            ->assertJsonPath('data.definition.body.order.reference', '{reference}')
            ->assertJsonPath('data.definition.headers.Accept', 'application/json')
            ->assertJsonPath('data.definition.connection', 'limenos-merchant');

        $this->getJson('/api/agentic/admin/tools/limenos-orders-create')
            ->assertOk()
            ->assertJsonPath('data.definition.body.order.reference', '{reference}')
            ->assertJsonPath('data.definition.query.status', '{status}');
    }

    public function test_admin_can_send_a_live_http_tool_request(): void
    {
        Http::fake([
            'https://api.example.test/orders/42' => Http::response(['id' => 42, 'status' => 'shipped'], 200),
        ]);

        $this->postJson('/api/agentic/admin/tools', [
            'name' => 'Get order',
            'slug' => 'orders-get',
            'driver' => 'http',
            'status' => 'published',
            'publish' => true,
            'definition' => [
                'method' => 'GET',
                'url' => 'https://api.example.test/orders/{id}',
                'headers' => ['Authorization' => 'Bearer secret-token'],
                'input_schema' => [
                    'type' => 'object',
                    'properties' => ['id' => ['type' => 'string']],
                    'required' => ['id'],
                ],
            ],
        ])->assertCreated();

        $this->postJson('/api/agentic/admin/tools/orders-get/test', [
            'arguments' => ['id' => '42'],
        ])
            ->assertOk()
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.data.id', 42)
            ->assertJsonPath('data.request.method', 'GET')
            ->assertJsonPath('data.request.url', 'https://api.example.test/orders/42')
            ->assertJsonPath('data.request.headers.Authorization', '[redacted]');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test/orders/42');
    }

    public function test_http_tool_test_rejects_non_http_tools(): void
    {
        $this->postJson('/api/agentic/admin/tools', [
            'name' => 'Code tool',
            'slug' => 'code-only',
            'driver' => 'code',
            'status' => 'published',
            'publish' => true,
            'definition' => ['handler' => 'App\\Tools\\Noop'],
        ])->assertCreated();

        $this->postJson('/api/agentic/admin/tools/code-only/test', [
            'arguments' => [],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tool [code-only] is not an HTTP tool.');
    }

    public function test_admin_can_clone_an_http_tool(): void
    {
        $this->postJson('/api/agentic/admin/tools', [
            'name' => 'Get ASN',
            'slug' => 'limenos-asns-get',
            'driver' => 'http',
            'status' => 'published',
            'publish' => true,
            'definition' => [
                'method' => 'GET',
                'url' => 'https://limenos.ai/api/v1/merchant/asns/{reference}',
                'connection' => 'limenos-merchant',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => ['reference' => ['type' => 'string']],
                    'required' => ['reference'],
                ],
            ],
        ])->assertCreated();

        $this->postJson('/api/agentic/admin/tools/limenos-asns-get/clone')
            ->assertCreated()
            ->assertJsonPath('data.slug', 'limenos-asns-get-copy')
            ->assertJsonPath('data.name', 'Get ASN (copy)')
            ->assertJsonPath('data.definition.url', 'https://limenos.ai/api/v1/merchant/asns/{reference}')
            ->assertJsonPath('data.definition.connection', 'limenos-merchant');

        $this->postJson('/api/agentic/admin/tools/limenos-asns-get/clone')
            ->assertCreated()
            ->assertJsonPath('data.slug', 'limenos-asns-get-copy-2');
    }

    public function test_updating_connection_replaces_the_published_definition(): void
    {
        $this->postJson('/api/agentic/admin/tools', [
            'name' => 'Get order',
            'slug' => 'orders-get',
            'driver' => 'http',
            'status' => 'published',
            'publish' => true,
            'definition' => [
                'method' => 'GET',
                'url' => 'https://limenos.ai/api/v1/merchant/orders/{reference}',
                'connection' => 'limenos-merchant',
            ],
        ])->assertCreated();

        $this->putJson('/api/agentic/admin/tools/orders-get', [
            'name' => 'Get order',
            'slug' => 'orders-get',
            'driver' => 'http',
            'status' => 'published',
            'publish' => false,
            'definition' => [
                'method' => 'GET',
                'url' => 'https://limenos.ai/api/v1/merchant/orders/{reference}',
                'connection' => 'limenos-staging',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.definition.connection', 'limenos-staging');

        $this->getJson('/api/agentic/admin/tools/orders-get')
            ->assertOk()
            ->assertJsonPath('data.definition.connection', 'limenos-staging');
    }
}
