<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WidgetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.conversation.driver', 'eloquent');
    }

    public function test_widget_config_requires_agent(): void
    {
        $this->getJson('/api/agentic/widget/config')
            ->assertStatus(422);
    }

    public function test_widget_config_for_published_agent(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $this->getJson('/api/agentic/widget/config?agent=support')
            ->assertOk()
            ->assertJsonPath('data.agent', 'support')
            ->assertJsonPath('data.realtime.driver', 'polling');
    }
}
