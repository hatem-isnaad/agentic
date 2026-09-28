<?php

namespace Agentic\Tests\Unit;

use Agentic\Channels\ChannelOutboundFactory;
use Agentic\Models\ChannelAccount;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

final class WhatsAppOutboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_cloud_posts_graph_text_message(): void
    {
        Http::fake(['https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200)]);

        $account = ChannelAccount::query()->create([
            'name' => 'Sales WA',
            'slug' => 'sales-wa',
            'channel' => 'whatsapp',
            'driver' => 'meta_cloud',
            'agent_slug' => 'support',
            'external_id' => '109876',
            'credentials' => ['access_token' => 'eaat-test'],
            'status' => 'active',
        ]);

        $ok = (new ChannelOutboundFactory)->for($account)->sendText($account, '20100000000', 'Hi there');

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v21.0/109876/messages')
                && $request['to'] === '20100000000'
                && $request['text']['body'] === 'Hi there';
        });
    }

    public function test_webjs_posts_sidecar_send(): void
    {
        Http::fake(['https://sidecar.test/send' => Http::response(['ok' => true], 200)]);

        $account = ChannelAccount::query()->create([
            'name' => 'Linked phone',
            'slug' => 'linked-phone',
            'channel' => 'whatsapp',
            'driver' => 'webjs',
            'agent_slug' => 'support',
            'external_id' => 'desk-1',
            'config' => ['sidecar_url' => 'https://sidecar.test'],
            'credentials' => ['sidecar_secret' => 'side-secret'],
            'status' => 'active',
        ]);

        $ok = (new ChannelOutboundFactory)->for($account)->sendText($account, '20100000000', 'Hi');

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sidecar.test/send'
                && $request->hasHeader('X-Agentic-Channel-Secret', 'side-secret')
                && $request['session'] === 'desk-1'
                && $request['to'] === '20100000000';
        });
    }
}
