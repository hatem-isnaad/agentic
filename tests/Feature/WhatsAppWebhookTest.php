<?php

namespace Agentic\Tests\Feature;

use Agentic\Agent\AgentDefinition;
use Agentic\Channels\ChannelAgentRunner;
use Agentic\Enums\Status;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;
use Agentic\Models\Agent;
use Agentic\Models\ChannelAccount;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

final class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.channels.whatsapp.verify_token', 'verify-me');
    }

    public function test_meta_verify_returns_challenge(): void
    {
        $this->get('/api/agentic/channels/whatsapp/meta?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=challenge-42')
            ->assertOk()
            ->assertSee('challenge-42');
    }

    public function test_meta_inbound_replies_via_graph(): void
    {
        Http::fake(['https://graph.facebook.com/*' => Http::response(['ok' => true], 200)]);
        $this->app->instance(ChannelAgentRunner::class, new class implements ChannelAgentRunner
        {
            public function run(AgentDefinition $agent, AgentExecutionContext $context): AgentExecutionResult
            {
                return AgentExecutionResult::success('We can help');
            }
        });

        Agent::query()->create(['name' => 'Support', 'slug' => 'support', 'status' => Status::Published, 'instructions' => 'Help']);
        ChannelAccount::query()->create([
            'name' => 'Sales',
            'slug' => 'sales-meta',
            'channel' => 'whatsapp',
            'driver' => 'meta_cloud',
            'agent_slug' => 'support',
            'external_id' => '109876',
            'credentials' => ['access_token' => 'eaat-test'],
            'status' => 'active',
        ]);

        $this->postJson('/api/agentic/channels/whatsapp/meta', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '109876'],
                        'messages' => [[
                            'id' => 'wamid.1',
                            'from' => '20100000000',
                            'type' => 'text',
                            'text' => ['body' => 'Need help'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk()->assertSee('EVENT_RECEIVED');

        $this->assertSame(2, ConversationMessage::query()->count());
        Http::assertSent(fn ($request) => str_contains($request->url(), '/109876/messages') && $request['text']['body'] === 'We can help');
    }

    public function test_webjs_inbound_uses_sidecar_secret(): void
    {
        Http::fake(['https://sidecar.test/send' => Http::response(['ok' => true], 200)]);
        $this->app->instance(ChannelAgentRunner::class, new class implements ChannelAgentRunner
        {
            public function run(AgentDefinition $agent, AgentExecutionContext $context): AgentExecutionResult
            {
                return AgentExecutionResult::success('Linked reply');
            }
        });

        Agent::query()->create(['name' => 'Support', 'slug' => 'support', 'status' => Status::Published, 'instructions' => 'Help']);
        ChannelAccount::query()->create([
            'name' => 'Desk',
            'slug' => 'desk-webjs',
            'channel' => 'whatsapp',
            'driver' => 'webjs',
            'agent_slug' => 'support',
            'external_id' => 'desk-1',
            'config' => ['sidecar_url' => 'https://sidecar.test'],
            'credentials' => ['sidecar_secret' => 'side-secret'],
            'status' => 'active',
        ]);

        $this->postJson('/api/agentic/channels/whatsapp/webjs', [
            'session' => 'desk-1',
            'from' => '20100000000',
            'text' => 'Hi',
        ], ['X-Agentic-Channel-Secret' => 'side-secret'])->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://sidecar.test/send' && $request['text'] === 'Linked reply');
    }
}
