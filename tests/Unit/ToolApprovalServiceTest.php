<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Tool\ToolApprovalService;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ToolApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_always_policy_requires_approval(): void
    {
        app(ToolFactory::class)->register(new ToolDefinition(
            name: 'orders.update',
            description: 'Update order',
            driver: 'code',
            configuration: ['handler' => 'noop'],
            approval: 'always',
        ));

        $tool = app(ToolRegistry::class)->resolve('orders.update');

        $this->assertTrue(app(ToolApprovalService::class)->requiresApproval($tool));
    }

    public function test_tools_without_an_approval_policy_do_not_use_name_heuristics(): void
    {
        app(ToolFactory::class)->register(new ToolDefinition(
            name: 'orders.delete',
            description: 'Delete order',
            driver: 'code',
            configuration: ['handler' => 'noop'],
        ));

        $tool = app(ToolRegistry::class)->resolve('orders.delete');

        $this->assertFalse(app(ToolApprovalService::class)->requiresApproval($tool));
    }
}
