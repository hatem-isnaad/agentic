<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminEvalSetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
    }

    public function test_eval_set_can_be_created_and_run_without_agent(): void
    {
        $this->postJson('/api/agentic/admin/eval-sets', [
            'name' => 'Support golden',
            'agent_slug' => 'missing-agent',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'support-golden');

        $this->postJson('/api/agentic/admin/eval-sets/support-golden/cases', [
            'question' => 'What is your refund policy?',
            'expect_contains' => ['refund'],
        ])->assertCreated();

        $this->getJson('/api/agentic/admin/eval-sets/support-golden')
            ->assertOk()
            ->assertJsonPath('data.cases.0.question', 'What is your refund policy?');

        $this->postJson('/api/agentic/admin/eval-sets/support-golden/run')
            ->assertOk()
            ->assertJsonPath('data.status', 'failed');
    }
}
