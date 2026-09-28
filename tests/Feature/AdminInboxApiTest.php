<?php

namespace Agentic\Tests\Feature;

use Agentic\Conversation\ConversationHandoffService;
use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Models\BroadcastEvent;
use Agentic\Tests\TestCase;
use Agentic\Widget\Support\WidgetReplyDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminInboxApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.widget.embed.require_token', false);
        $app['config']->set('agentic.widget.async_replies', true);
    }

    public function test_staff_can_take_reply_and_release(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $started = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Need help',
        ], [
            'X-Agentic-Guest-Id' => 'guest-inbox',
        ])->assertOk();

        $id = (string) $started->json('data.conversation_id');
        app(ConversationHandoffService::class)->request($id, 'widget ask', 'widget');

        $this->getJson('/api/agentic/admin/inbox')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.needs_human', true)
            ->assertJsonPath('data.0.handoff_status', ConversationHandoffService::Requested);

        $this->postJson('/api/agentic/admin/conversations/'.$id.'/take', ['by' => 'sara@example.com'])
            ->assertOk()
            ->assertJsonPath('data.metadata.handoff.status', ConversationHandoffService::Taken);

        $this->assertTrue(
            BroadcastEvent::query()
                ->where('channel', WidgetReplyDelivery::conversationChannel($id))
                ->where('event', 'handoff.updated')
                ->exists(),
        );

        $this->postJson('/api/agentic/admin/conversations/'.$id.'/reply', ['message' => 'I am here.'])
            ->assertCreated()
            ->assertJsonPath('data.html', '<p>I am here.</p>');

        $this->postJson('/api/agentic/admin/conversations/'.$id.'/release')
            ->assertOk()
            ->assertJsonPath('data.metadata.handoff.status', ConversationHandoffService::None);

        $this->assertSame(
            2,
            BroadcastEvent::query()
                ->where('channel', WidgetReplyDelivery::conversationChannel($id))
                ->where('event', 'handoff.updated')
                ->count(),
        );
    }
}
