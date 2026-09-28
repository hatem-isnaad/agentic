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

final class MessengerWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.channels.messenger.verify_token', 'verify-me');
        $app['config']->set('agentic.channels.verify_signatures', false);
    }

    public function test_meta_verify_returns_challenge(): void
    {
        $this->get('/api/agentic/channels/messenger/meta?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=challenge-99')
            ->assertOk()
            ->assertSee('challenge-99');
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
            'name' => 'Page',
            'slug' => 'sales-page',
            'channel' => 'messenger',
            'driver' => 'meta_cloud',
            'agent_slug' => 'support',
            'external_id' => '109876',
            'credentials' => ['access_token' => 'eaat-test'],
            'status' => 'active',
        ]);

        $this->postJson('/api/agentic/channels/messenger/meta', [
            'entry' => [[
                'id' => '109876',
                'messaging' => [[
                    'sender' => ['id' => 'psid-1'],
                    'recipient' => ['id' => '109876'],
                    'message' => ['mid' => 'mid.1', 'text' => 'Need help'],
                ]],
            ]],
        ])->assertOk()->assertSee('EVENT_RECEIVED');

        $this->assertSame(2, ConversationMessage::query()->count());
        Http::assertSent(fn ($request) => str_contains($request->url(), '/109876/messages') && ($request['message']['text'] ?? null) === 'We can help');
    }
}
