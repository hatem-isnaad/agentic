<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\WidgetEmbedToken;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WidgetEmbedTokenAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_embed_token_and_receive_plain_once(): void
    {
        $res = $this->postJson('/api/agentic/admin/widget-embed-tokens', [
            'name' => 'ci',
            'allowed_agents' => ['support'],
            'guest_allowed' => true,
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.name', 'ci')
            ->assertJsonStructure(['data' => ['plain_token', 'token_prefix']]);

        $plain = (string) $res->json('data.plain_token');
        $this->assertStringStartsWith('wgt_', $plain);
        $this->assertSame(1, WidgetEmbedToken::query()->count());
    }

    public function test_admin_can_list_tokens_without_plain(): void
    {
        WidgetEmbedToken::query()->create([
            'name' => 'x',
            'token_prefix' => 'wgt_abc12345',
            'token_hash' => bcrypt('wgt_secret'),
            'enabled' => true,
            'guest_allowed' => true,
            'sanctum_allowed' => true,
        ]);

        $this->getJson('/api/agentic/admin/widget-embed-tokens')
            ->assertOk()
            ->assertJsonMissing(['plain_token']);
    }
}
