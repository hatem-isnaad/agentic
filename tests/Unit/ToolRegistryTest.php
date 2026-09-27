<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class ToolRegistryTest extends TestCase
{
    public function test_tool_can_be_registered_and_resolved(): void
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
        $this->assertSame($tool, $registry->get('ping'));
    }
}
