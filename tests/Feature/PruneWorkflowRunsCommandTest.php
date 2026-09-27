<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\WorkflowRun;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

final class PruneWorkflowRunsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_deletes_old_workflow_runs(): void
    {
        $old = WorkflowRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'workflow_slug' => 'old-flow',
            'status' => 'completed',
            'step_pointer' => 0,
            'variables' => [],
            'trace' => [],
        ]);
        $old->forceFill([
            'updated_at' => now()->subDays(120),
            'created_at' => now()->subDays(120),
        ])->saveQuietly();

        WorkflowRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'workflow_slug' => 'new-flow',
            'status' => 'completed',
            'step_pointer' => 0,
            'variables' => [],
            'trace' => [],
        ]);

        $this->artisan('agentic:prune-workflow-runs', ['--days' => 90])
            ->assertSuccessful();

        $this->assertSame(1, WorkflowRun::query()->count());
        $this->assertSame('new-flow', WorkflowRun::query()->value('workflow_slug'));
    }
}
