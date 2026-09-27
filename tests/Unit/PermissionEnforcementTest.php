<?php

namespace Agentic\Tests\Unit;

use Agentic\Integrations\LaravelAi\AgenticLaravelTool;
use Agentic\Permission\DenyAllPermissionChecker;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;
use Laravel\Ai\Tools\Request;

final class PermissionEnforcementTest extends TestCase
{
    public function test_denied_tool_never_executes_driver(): void
    {
        $executed = false;

        $tool = new class($executed) implements ToolContract {
            public function __construct(private bool &$executed) {}

            public function definition(): ToolDefinition
            {
                return new ToolDefinition('secure.action', 'A secured action.');
            }

            public function execute(ToolExecutionContext $context): ToolResult
            {
                $this->executed = true;

                return ToolResult::success('should-not-run');
            }
        };

        $adapter = new AgenticLaravelTool($tool, new DenyAllPermissionChecker());

        $result = $adapter->handle(new Request(['x' => 1]));

        $this->assertFalse($executed);
        $this->assertStringContainsString('Permission denied', (string) $result);
    }
}
