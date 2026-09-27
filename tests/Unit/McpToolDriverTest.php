<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Tool\ConfiguredTool;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway;
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;

final class McpToolDriverTest extends TestCase
{
    private ArrayMcpClientGateway $mcp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mcp = new ArrayMcpClientGateway();
        $this->app->instance(McpClientGateway::class, $this->mcp);
    }

    public function test_mcp_tool_executes_through_gateway(): void
    {
        $this->mcp->registerServer('warehouse', [
            [
                'name' => 'locate_bin',
                'description' => 'Locate a warehouse bin',
                'input_schema' => [
                    'sku' => ['type' => 'string', 'required' => true],
                ],
                'handler' => fn (array $args) => ['bin' => 'A-1', 'sku' => $args['sku']],
            ],
        ]);

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'warehouse.locate_bin',
                description: 'Locate bin',
                inputSchema: [
                    'sku' => ['type' => 'string', 'required' => true],
                ],
                driver: 'mcp',
                configuration: [
                    'server' => 'warehouse',
                    'tool' => 'locate_bin',
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(
            arguments: ['sku' => 'SKU-9'],
        ));

        $this->assertTrue($result->success);
        $this->assertSame(['bin' => 'A-1', 'sku' => 'SKU-9'], $result->data);
    }

    public function test_mcp_registrar_imports_server_tools_into_registry(): void
    {
        $this->mcp->registerServer('crm', [
            [
                'name' => 'search_customer',
                'description' => 'Search customers',
                'handler' => fn () => ['ok' => true],
            ],
        ]);

        $names = app(McpToolRegistrar::class)->registerServer('crm', 'mcp.crm.');

        $this->assertSame(['mcp.crm.search_customer'], $names);
        $this->assertTrue(app(ToolRegistry::class)->has('mcp.crm.search_customer'));

        $result = app(ToolRegistry::class)
            ->resolve('mcp.crm.search_customer')
            ->execute(new ToolExecutionContext());

        $this->assertTrue($result->success);
        $this->assertSame(['ok' => true], $result->data);
    }

    public function test_mcp_tool_requires_registered_server(): void
    {
        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'missing',
                description: 'Missing server',
                driver: 'mcp',
                configuration: [
                    'server' => 'nope',
                    'tool' => 'x',
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext());

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not registered', (string) $result->error);
    }
}
