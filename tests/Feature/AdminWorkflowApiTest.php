<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Agentic\Tool\ToolApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.workflows.driver', 'eloquent');
    }

    public function test_admin_workflow_crud_execute_and_list_runs(): void
    {
        $prefix = trim((string) config('agentic.admin.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/workflows', [
            'name' => 'Admin flow',
            'slug' => 'admin-flow',
            'status' => 'published',
            'steps' => [
                ['id' => 'confirm', 'type' => 'approval', 'title' => 'Confirm'],
                ['id' => 'done', 'type' => 'complete', 'output' => ['ok' => true]],
            ],
        ])->assertCreated();

        $this->getJson('/'.$prefix.'/workflows/admin-flow')
            ->assertOk()
            ->assertJsonPath('data.slug', 'admin-flow');

        $pending = $this->postJson('/'.$prefix.'/workflows/admin-flow/execute', [
            'input' => [],
        ])->assertStatus(202);

        $runId = $pending->json('data.workflow_run_id');
        $approvalId = $pending->json('data.approval_id');

        $this->getJson('/'.$prefix.'/workflow-runs?status=pending_approval')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $runId);

        app(ToolApprovalService::class)->approve(
            app(ToolApprovalService::class)->find($approvalId),
        );

        $this->postJson('/'.$prefix.'/workflows/admin-flow/resume', [
            'approval_id' => $approvalId,
        ])
            ->assertOk()
            ->assertJsonPath('data.output.ok', true);

        $this->getJson('/'.$prefix.'/workflow-runs/'.$runId)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }
}
