<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Permission\DenyAllPermissionChecker;
use Agentic\Permission\PermissionResolver;
use Agentic\Tool\ConfiguredTool;
use Agentic\Tool\DriverResolver;
use Agentic\Tool\ToolDefinition;
use Agentic\Tests\TestCase;
use Agentic\Context\RuntimeContext;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\Contracts\ToolDriver;
use Illuminate\Container\Container;

final class PermissionResolverTest extends TestCase
{
    public function test_permission_is_denied_by_default(): void
    {
        $resolver = new PermissionResolver(
            new DenyAllPermissionChecker(),
            new Container(),
        );

        $tool = new ConfiguredTool(
            new ToolDefinition('orders.read', 'Read orders'),
            new DriverResolver(new Container()),
        );

        $decision = $resolver->decide($tool, new AgentDefinition('support'), new RuntimeContext());

        $this->assertTrue($decision->allowed() === false);
    }

    public function test_explicit_agent_permission_can_allow_a_tool(): void
    {
        $resolver = new PermissionResolver(
            new DenyAllPermissionChecker(),
            new Container(),
        );

        $tool = new ConfiguredTool(
            new ToolDefinition('orders.read', 'Read orders'),
            new DriverResolver(new Container()),
        );

        $decision = $resolver->decide(
            $tool,
            new AgentDefinition('support', permissions: ['orders.read']),
            new RuntimeContext(),
        );

        $this->assertTrue($decision->allowed());
    }
}
