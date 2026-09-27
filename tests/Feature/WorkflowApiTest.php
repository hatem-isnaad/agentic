<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.workflows.driver', 'eloquent');
    }

    public function test_workflow_can_be_created_and_executed_via_api(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/workflows', [
            'name' => 'Greet',
            'slug' => 'greet',
            'status' => 'published',
            'steps' => [
                ['id' => 'seed', 'type' => 'set', 'variables' => ['name' => '{input.name}']],
                ['id' => 'done', 'type' => 'complete', 'output' => ['message' => 'Hello {name}']],
            ],
        ])->assertCreated();

        $this->postJson('/'.$prefix.'/workflows/greet/execute', [
            'input' => ['name' => 'Agentic'],
        ])
            ->assertOk()
            ->assertJsonPath('data.output.message', 'Hello Agentic');
    }
}
