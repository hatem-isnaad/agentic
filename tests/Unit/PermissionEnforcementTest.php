<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\RuntimeContext;
use Agentic\Events\PermissionDenied;
use Agentic\Permission\DenyAllPermissionChecker;
use Agentic\Permission\PermissionResolver;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolResult;
use Illuminate\Support\Facades\Event;

final class PermissionEnforcementTest extends TestCase
{
    public function test_denied_tool_never_executes_driver(): void
    {
        Event::fake([PermissionDenied::class]);

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

        $this->app->instance(
            \Agentic\Permission\PermissionChecker::class,
            new DenyAllPermissionChecker(),
        );

        $executor = new ToolExecutor(
            new PermissionResolver(new DenyAllPermissionChecker(), $this->app),
            $this->app['events'],
        );

        $result = $executor->execute($tool, new ToolExecutionContext(
            runtime: new RuntimeContext([
                'agent' => new AgentDefinition(name: 'Ops', slug: 'ops'),
            ]),
        ));

        $this->assertFalse($executed);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Permission denied', (string) $result->error);
        Event::assertDispatched(PermissionDenied::class);
    }

    public function test_agent_permission_allow_list_blocks_unlisted_tools(): void
    {
        $executed = false;

        $tool = new class($executed) implements ToolContract {
            public function __construct(private bool &$executed) {}

            public function definition(): ToolDefinition
            {
                return new ToolDefinition('orders.cancel', 'Cancel order');
            }

            public function execute(ToolExecutionContext $context): ToolResult
            {
                $this->executed = true;

                return ToolResult::success('cancelled');
            }
        };

        $result = app(ToolExecutor::class)->execute($tool, new ToolExecutionContext(
            runtime: new RuntimeContext([
                'agent' => new AgentDefinition(
                    name: 'Support',
                    slug: 'support',
                    permissions: ['orders.get'],
                ),
            ]),
        ));

        $this->assertFalse($executed);
        $this->assertFalse($result->success);
    }
}
