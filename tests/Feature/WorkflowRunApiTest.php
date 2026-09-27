<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WorkflowRunApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.workflows.driver', 'eloquent');
    }

    public function test_workflow_run_index_lists_recent_runs(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/workflows', [
            'name' => 'Greet',
            'slug' => 'greet',
            'status' => 'published',
            'steps' => [
                ['id' => 'done', 'type' => 'complete', 'output' => ['ok' => true]],
            ],
        ])->assertCreated();

        $execute = $this->postJson('/'.$prefix.'/workflows/greet/execute', [
            'input' => [],
        ])->assertOk();

        $runId = $execute->json('data.workflow_run_id');

        $this->getJson('/'.$prefix.'/workflow-runs?workflow_slug=greet')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $runId)
            ->assertJsonPath('data.0.status', 'completed');
    }
}
