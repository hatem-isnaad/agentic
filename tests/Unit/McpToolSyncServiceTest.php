<?php

namespace Agentic\Tests\Unit;

use Agentic\Mcp\McpToolSyncService;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tests\TestCase;

final class McpToolSyncServiceTest extends TestCase
{
    public function test_sync_registers_discovered_tools(): void
    {
        $gateway = new ArrayMcpClientGateway();
        $gateway->registerServer('crm', [[
            'name' => 'lookup_customer',
            'description' => 'Lookup customer',
            'input_schema' => ['type' => 'object'],
            'handler' => fn () => ['ok' => true],
        ]]);

        $this->app->instance(McpClientGateway::class, $gateway);
        config(['mcp.servers' => ['crm' => []]]);

        $tools = app(McpToolSyncService::class)->sync('crm');

        $this->assertSame(['lookup_customer'], $tools);
        $this->assertTrue(app(ToolRegistry::class)->has('lookup_customer'));
    }
}
