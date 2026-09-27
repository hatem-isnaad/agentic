<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Skill;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AgentApiCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
    }

    public function test_agent_crud_via_api(): void
    {
        Skill::create([
            'name' => 'Orders',
            'slug' => 'orders',
            'status' => Status::Published,
        ]);

        $this->postJson('/api/agentic/agents', [
            'name' => 'Support',
            'slug' => 'support',
            'instructions' => 'Help customers.',
            'status' => 'published',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'skills' => ['orders'],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'support')
            ->assertJsonPath('data.skills.0', 'orders');

        $this->putJson('/api/agentic/agents/support', [
            'name' => 'Support Pro',
            'slug' => 'support',
            'status' => 'published',
            'skills' => ['orders'],
        ])->assertOk()->assertJsonPath('data.name', 'Support Pro');

        $this->deleteJson('/api/agentic/agents/support')->assertNoContent();
    }
}
