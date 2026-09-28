<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Models\WidgetEmbedToken;
use Agentic\Support\WidgetOnlyMode;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

final class WidgetOnlyExposeTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.deploy.mode', 'widget');
        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.widget.embed.require_token', true);
        $app['config']->set('agentic.widget.embed.token', 'wgt_test_widget_only');
        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.auth.enabled', true);
    }

    public function test_admin_and_runtime_routes_are_not_registered(): void
    {
        $adminPrefix = trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/');
        $apiPrefix = trim((string) config('agentic.api.prefix', 'api/agentic'), '/');
        $authPrefix = trim((string) config('agentic.auth.prefix', 'api/agentic/auth'), '/');

        $this->getJson('/'.$adminPrefix.'/inbox')->assertNotFound();
        $this->getJson('/'.$apiPrefix.'/agents')->assertNotFound(); // runtime API
        $this->postJson('/'.$authPrefix.'/token')->assertNotFound();
    }

    public function test_widget_config_still_available_with_embed_token(): void
    {
        $this->assertTrue(WidgetOnlyMode::enabled());

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $plain = 'wgt_test_'.str_repeat('b', 40);
        WidgetEmbedToken::query()->create([
            'name' => 'widget-only',
            'token_prefix' => substr($plain, 0, 12),
            'token_hash' => Hash::make($plain),
            'guest_allowed' => true,
            'sanctum_allowed' => true,
            'enabled' => true,
        ]);

        $widgetPrefix = trim((string) config('agentic.widget.prefix', 'api/agentic/widget'), '/');

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->withHeader('Origin', 'http://localhost')
            ->withHeader('X-Agentic-Guest-Id', 'guest-widget-only')
            ->getJson('/'.$widgetPrefix.'/config?agent=support')
            ->assertOk();
    }
}
