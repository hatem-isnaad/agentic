<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalService;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WidgetApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.widget.embed.require_token', false);
        $app['config']->set('agentic.tool_approval.auto_execute_on_approve', false);
    }

    public function test_widget_can_approve_pending_tool_approval(): void
    {
        app(ToolFactory::class)->register(new ToolDefinition(
            name: 'orders.update',
            description: 'Update order',
            driver: 'code',
            configuration: ['handler' => 'noop'],
            approval: 'always',
        ));

        $tool = app(ToolRegistry::class)->resolve('orders.update');
        $approval = app(ToolApprovalService::class)->createPending(
            $tool,
            ['id' => 1],
            agent: 'support',
        );

        $prefix = trim((string) config('agentic.widget.prefix'), '/');

        $this->postJson('/'.$prefix.'/approvals/'.$approval->uuid.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.tool', 'orders.update');
    }
}
