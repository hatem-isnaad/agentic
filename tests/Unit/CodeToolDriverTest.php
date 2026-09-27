<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Tool\ConfiguredTool;
use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class CodeToolDriverTest extends TestCase
{
    public function test_code_tool_invokes_registered_handler(): void
    {
        $handlers = app(HandlerRegistry::class);
        $handlers->register('orders.search', new class implements CodeToolHandler {
            public function handle(ToolExecutionContext $context): ToolResult
            {
                return ToolResult::success([
                    'query' => $context->arguments['query'] ?? null,
                    'hits' => 1,
                ]);
            }
        });

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.search',
                description: 'Search orders',
                inputSchema: [
                    'query' => ['type' => 'string', 'required' => true],
                ],
                driver: 'code',
                configuration: [
                    'handler' => 'orders.search',
                ],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(
            arguments: ['query' => 'ORD-1'],
        ));

        $this->assertTrue($result->success);
        $this->assertSame(['query' => 'ORD-1', 'hits' => 1], $result->data);
    }

    public function test_code_tool_supports_closure_handlers(): void
    {
        app(HandlerRegistry::class)->register(
            'math.add',
            fn (ToolExecutionContext $context) => ($context->arguments['a'] ?? 0) + ($context->arguments['b'] ?? 0),
        );

        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'math.add',
                description: 'Add numbers',
                driver: 'code',
                configuration: ['handler' => 'math.add'],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext(
            arguments: ['a' => 2, 'b' => 3],
        ));

        $this->assertTrue($result->success);
        $this->assertSame(5, $result->data);
    }

    public function test_code_tool_rejects_unregistered_handler(): void
    {
        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'orders.cancel',
                description: 'Cancel order',
                driver: 'code',
                configuration: ['handler' => 'missing.handler'],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext());

        $this->assertFalse($result->success);
        $this->assertStringContainsString('handler:missing.handler', (string) $result->error);
    }

    public function test_code_tool_refuses_raw_php_handler_reference(): void
    {
        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'evil.tool',
                description: 'Should fail',
                driver: 'code',
                configuration: ['handler' => "<?php echo 'no';"],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext());

        $this->assertFalse($result->success);
        $this->assertStringContainsString('untrusted handler', (string) $result->error);
    }

    public function test_code_tool_requires_handler_configuration(): void
    {
        $tool = new ConfiguredTool(
            new ToolDefinition(
                name: 'broken',
                description: 'Missing handler',
                driver: 'code',
                configuration: [],
            ),
            app(DriverResolver::class),
        );

        $result = $tool->execute(new ToolExecutionContext());

        $this->assertFalse($result->success);
        $this->assertStringContainsString('registered handler name', (string) $result->error);
    }
}
