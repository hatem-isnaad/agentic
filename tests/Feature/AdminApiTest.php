<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.execution.driver', 'eloquent');
    }

    public function test_admin_dashboard_and_translations(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
        ]);

        $this->getJson('/api/agentic/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.agents', 1)
            ->assertJsonPath('meta.locale', 'en');

        $this->getJson('/api/agentic/admin/translations')
            ->assertOk()
            ->assertJsonPath('data.en.nav.dashboard', 'Dashboard')
            ->assertJsonPath('data.en.guide.title', 'Full setup guide');

        $this->getJson('/api/agentic/admin/settings')
            ->assertOk()
            ->assertJsonPath('data.features.admin_api', true);
    }
}
