<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Models\WidgetEmbedToken;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

final class WidgetEmbedAuthTest extends TestCase
{
    use RefreshDatabase;
    public function test_widget_config_requires_embed_token_when_enforced(): void
    {
        $this->app['config']->set('agentic.widget.embed.require_token', true);

        $this->getJson('/api/agentic/widget/config?agent=support')
            ->assertUnauthorized();
    }

    public function test_widget_config_accepts_valid_embed_token(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $this->app['config']->set('agentic.widget.embed.require_token', true);
        $plain = 'wgt_test_'.str_repeat('a', 40);
        WidgetEmbedToken::query()->create([
            'name' => 'test',
            'token_prefix' => substr($plain, 0, 12),
            'token_hash' => Hash::make($plain),
            'guest_allowed' => true,
            'sanctum_allowed' => true,
            'enabled' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->withHeader('Origin', 'http://localhost')
            ->withHeader('X-Agentic-Guest-Id', 'guest-test-1')
            ->getJson('/api/agentic/widget/config?agent=support')
            ->assertOk()
            ->assertJsonPath('data.embed.guest_allowed', true)
            ->assertJsonPath('data.embed.auth_required', false);
    }
}
