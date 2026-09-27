<?php

namespace Agentic\Tests\Unit;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class ToolRegistryTest extends TestCase
{
    public function test_tool_can_be_registered_resolved_and_unregistered(): void
    {
        $registry = new ToolRegistry();

        $tool = new class implements ToolContract {
            public function definition(): ToolDefinition
            {
                return new ToolDefinition('ping', 'Ping the system.');
            }

            public function execute(ToolExecutionContext $context): ToolResult
            {
                return ToolResult::success('pong');
            }
        };

        $registry->register($tool);

        $this->assertTrue($registry->has('ping'));
        $this->assertSame($tool, $registry->resolve('ping'));
        $this->assertSame($tool, $registry->find('ping'));

        $registry->unregister('ping');

        $this->assertFalse($registry->has('ping'));
        $this->assertNull($registry->find('ping'));
        $this->expectException(ToolNotFoundException::class);
        $registry->get('ping');
    }
}
