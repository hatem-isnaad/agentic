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
        $this->assertNotEmpty($approvalId);

        app(ToolApprovalService::class)->approve(
            app(ToolApprovalService::class)->find($approvalId),
        );

        $this->postJson('/'.$prefix.'/workflows/approve-flow/resume', [
            'approval_id' => $approvalId,
            'input' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.output.ok', true);
    }
}
