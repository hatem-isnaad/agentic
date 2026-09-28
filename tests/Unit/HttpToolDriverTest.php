<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Tool\ConfiguredTool;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Illuminate\Support\Facades\Http;

final class HttpToolDriverTest extends TestCase
{
    public function test_http_tool_executes_get_with_path_and_query_mapping(): void
    {
        Http::fake([
            'https://api.example.test/orders/42*' => Http::response([
                'id' => 42,
                'status' => 'shipped',
            ], 200),
        ]);

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.get',
                description: 'Get an order',
                inputSchema: [
                    'id' => ['type' => 'integer', 'required' => true],
                ],
                driver: 'http',
                configuration: [
                    'method' => 'GET',
                    'url' => 'https://api.example.test/orders/{id}',
                    'query' => ['include' => 'items'],
                    'headers' => ['X-Shop' => '{shop}'],
                    'auth' => ['type' => 'bearer', 'token' => 'secret-token'],
                    'response_mapping' => [
                        'order_id' => 'body.id',
                        'status' => 'body.status',
                    ],
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(
            arguments: ['id' => 42, 'shop' => 'acme'],
        ));

        $this->assertTrue($result->success);
        $this->assertSame(['order_id' => 42, 'status' => 'shipped'], $result->data);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.test/orders/42?include=items'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request->hasHeader('X-Shop', 'acme');
        });
    }

    public function test_http_tool_posts_json_body_and_retries_configuration(): void
    {
        Http::fake([
            'https://api.example.test/orders' => Http::response(['ok' => true], 201),
        ]);

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.create',
                description: 'Create an order',
                driver: 'http',
                configuration: [
                    'method' => 'POST',
                    'url' => 'https://api.example.test/orders',
                    'body' => [
                        'sku' => '{sku}',
                        'qty' => '{qty}',
                    ],
                    'timeout' => 5,
                    'retry' => ['times' => 1, 'sleep' => 1],
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(
            arguments: ['sku' => 'ABC', 'qty' => 2],
        ));

        $this->assertTrue($result->success);
        $this->assertSame(['ok' => true], $result->data);

        Http::assertSent(fn ($request) => $request['sku'] === 'ABC' && $request['qty'] === '2');
    }

    public function test_http_tool_retries_retryable_status_for_idempotent_method(): void
    {
        Http::fakeSequence()
            ->push(['message' => 'busy'], 429)
            ->push(['id' => 42], 200);

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.get',
                description: 'Get an order',
                driver: 'http',
                configuration: [
                    'method' => 'GET',
                    'url' => 'https://api.example.test/orders/42',
                    'retry' => ['times' => 1, 'sleep' => 1],
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext);

        $this->assertTrue($result->success);
        $this->assertSame(['id' => 42], $result->data);
        Http::assertSentCount(2);
    }

    public function test_http_tool_maps_api_error_payload(): void
    {
        Http::fake([
            'https://api.example.test/orders/42' => Http::response([
                'error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Missing'],
            ], 404),
        ]);

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.get',
                description: 'Get an order',
                driver: 'http',
                configuration: [
                    'method' => 'GET',
                    'url' => 'https://api.example.test/orders/42',
                    'error_mapping' => [
                        'code' => 'body.error.code',
                        'message' => 'body.error.message',
                    ],
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('ORDER_NOT_FOUND', (string) $result->error);
        $this->assertStringContainsString('Missing', (string) $result->error);
    }

    public function test_http_tool_rejects_unresolved_url_placeholders(): void
    {
        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.get',
                description: 'Get an order',
                driver: 'http',
                configuration: [
                    'method' => 'GET',
                    'url' => 'https://api.example.test/orders/{id}',
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(arguments: []));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('unresolved URL placeholders', (string) $result->error);
    }

    public function test_http_tool_rejects_missing_required_input(): void
    {
        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.get',
                description: 'Get an order',
                inputSchema: [
                    'id' => ['type' => 'integer', 'required' => true],
                ],
                driver: 'http',
                configuration: [
                    'method' => 'GET',
                    'url' => 'https://api.example.test/orders/{id}',
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(arguments: []));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Missing required argument [id]', (string) $result->error);
    }

    public function test_http_tool_normalizes_http_errors(): void
    {
        Http::fake([
            'https://api.example.test/orders/1' => Http::response(['message' => 'gone'], 404),
        ]);

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.get',
                description: 'Get an order',
                driver: 'http',
                configuration: [
                    'method' => 'GET',
                    'url' => 'https://api.example.test/orders/1',
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('status 404', (string) $result->error);
    }
}
