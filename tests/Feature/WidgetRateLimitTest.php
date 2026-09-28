<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WidgetRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.widget.embed.require_token', false);
        $app['config']->set('agentic.widget.rate_limit.enabled', true);
        $app['config']->set('agentic.widget.rate_limit.per_minute', 1);
    }

    public function test_widget_returns_429_after_cap(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $this->getJson('/api/agentic/widget/config?agent=support')->assertOk();
        $this->getJson('/api/agentic/widget/config?agent=support')->assertStatus(429);
    }
}
