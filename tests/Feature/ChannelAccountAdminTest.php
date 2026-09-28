<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\ChannelAccount;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ChannelAccountAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_list_drivers_per_channel(): void
    {
        $this->getJson('/api/agentic/admin/channel-accounts/options')
            ->assertOk()
            ->assertJsonFragment(['channel' => 'widget', 'drivers' => ['embed']])
            ->assertJsonFragment(['channel' => 'whatsapp', 'drivers' => ['meta_cloud', 'webjs']]);
    }

    public function test_can_attach_widget_and_multiple_whatsapp_numbers(): void
    {
        $this->postJson('/api/agentic/admin/channel-accounts', [
            'name' => 'Web chat',
            'slug' => 'web-chat',
            'channel' => 'widget',
            'driver' => 'embed',
            'agent_slug' => 'support',
        ])->assertCreated()->assertJsonPath('data.channel', 'widget');

        $this->postJson('/api/agentic/admin/channel-accounts', [
            'name' => 'Sales Meta',
            'slug' => 'sales-meta',
            'channel' => 'whatsapp',
            'driver' => 'meta_cloud',
            'agent_slug' => 'support',
            'external_id' => '111',
            'display_number' => '+20100000001',
            'credentials' => ['access_token' => 'eaat-1', 'app_secret' => 'secret', 'verify_token' => 'verify'],
        ])->assertCreated();

        $this->postJson('/api/agentic/admin/channel-accounts', [
            'name' => 'Support linked',
            'slug' => 'support-webjs',
            'channel' => 'whatsapp',
            'driver' => 'webjs',
            'agent_slug' => 'support',
            'external_id' => 'desk-1',
            'config' => ['sidecar_url' => 'https://sidecar.test'],
            'credentials' => ['sidecar_secret' => 'side-secret'],
        ])->assertCreated();

        $this->assertSame(3, ChannelAccount::query()->count());
        $this->getJson('/api/agentic/admin/channel-accounts')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    public function test_rejects_webjs_on_widget_channel(): void
    {
        $this->postJson('/api/agentic/admin/channel-accounts', [
            'name' => 'Bad',
            'slug' => 'bad',
            'channel' => 'widget',
            'driver' => 'webjs',
        ])->assertStatus(422);
    }
}
