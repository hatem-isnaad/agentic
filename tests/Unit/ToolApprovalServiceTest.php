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

    public function test_write_pattern_requires_approval(): void
    {
        app(ToolFactory::class)->register(new ToolDefinition(
            name: 'orders.write',
            description: 'Write order',
            driver: 'code',
            configuration: ['handler' => 'noop'],
        ));

        $tool = app(ToolRegistry::class)->resolve('orders.write');
        $service = app(ToolApprovalService::class);

        $this->assertTrue($service->requiresApproval($tool));
    }
}
