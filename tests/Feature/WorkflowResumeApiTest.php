<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Agentic\Tool\ToolApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WorkflowResumeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.workflows.driver', 'eloquent');
    }

    public function test_workflow_resume_completes_after_approval(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/workflows', [
            'name' => 'Approve flow',
            'slug' => 'approve-flow',
            'status' => 'published',
            'steps' => [
                [
                    'id' => 'confirm',
                    'type' => 'approval',
                    'title' => 'Confirm',
                ],
                ['id' => 'done', 'type' => 'complete', 'output' => ['ok' => true]],
            ],
        ])->assertCreated();

        $pending = $this->postJson('/'.$prefix.'/workflows/approve-flow/execute', [
            'input' => [],
        ]);

        $pending->assertStatus(202);
        $approvalId = $pending->json('data.approval_id');
        $runId = $pending->json('data.workflow_run_id');
        $this->assertNotEmpty($approvalId);
        $this->assertNotEmpty($runId);

        $this->getJson('/'.$prefix.'/workflow-runs/'.$runId)
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval')
            ->assertJsonPath('data.approval_id', $approvalId);

        app(ToolApprovalService::class)->approve(
            app(ToolApprovalService::class)->find($approvalId),
        );

        $this->postJson('/'.$prefix.'/workflows/approve-flow/resume', [
            'approval_id' => $approvalId,
            'input' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.workflow_run_id', $runId)
            ->assertJsonPath('data.output.ok', true);

        $this->getJson('/'.$prefix.'/workflow-runs/'.$runId)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_workflow_resume_preserves_variables_set_before_approval(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/workflows', [
            'name' => 'Variable flow',
            'slug' => 'variable-flow',
            'status' => 'published',
            'steps' => [
                ['id' => 'seed', 'type' => 'set', 'variables' => ['code' => '{input.code}']],
                ['id' => 'confirm', 'type' => 'approval', 'title' => 'Confirm'],
                ['id' => 'done', 'type' => 'complete', 'output' => ['code' => '{code}']],
            ],
        ])->assertCreated();

        $pending = $this->postJson('/'.$prefix.'/workflows/variable-flow/execute', [
            'input' => ['code' => 'ABC-99'],
        ])->assertStatus(202);

        $approvalId = $pending->json('data.approval_id');

        app(ToolApprovalService::class)->approve(
            app(ToolApprovalService::class)->find($approvalId),
        );

        $this->postJson('/'.$prefix.'/workflows/variable-flow/resume', [
            'approval_id' => $approvalId,
            'input' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.output.code', 'ABC-99');
    }
}
