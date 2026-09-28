<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Jobs\ProcessWidgetBatchedAgentTurnJob;
use Agentic\Jobs\ProcessWidgetMessageJob;
use Agentic\Models\Agent;
use Agentic\Models\BroadcastEvent;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Agentic\Widget\Support\WidgetReplyDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

final class WidgetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.widget.embed.require_token', false);
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
            ->assertJsonPath('data.agent.slug', 'support')
            ->assertJsonPath('data.agent.name', 'Support')
            ->assertJsonPath('data.realtime.driver', 'polling')
            ->assertJsonPath('data.realtime.interval_ms', 3000)
            ->assertJsonPath('data.embed.require_token', false)
            ->assertJsonPath('data.handoff.enabled', true)
            ->assertJsonPath('data.attachments.enabled', true)
            ->assertJsonPath('data.stream', false)
            ->assertJsonPath('data.conversation.resume_after_hours', 24)
            ->assertJsonMissingPath('data.providers')
            ->assertJsonMissingPath('meta');
    }

    public function test_widget_config_uses_persona_display_name(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
            'config' => [
                'persona' => [
                    'display_name' => 'Noura',
                    'gender' => 'female',
                    'language' => 'ar',
                    'dialect' => 'saudi',
                    'tone' => 'warm',
                ],
            ],
        ]);

        $this->getJson('/api/agentic/widget/config?agent=support')
            ->assertOk()
            ->assertJsonPath('data.agent.slug', 'support')
            ->assertJsonPath('data.agent.name', 'Noura');
    }

    public function test_widget_config_pusher_realtime_exposes_public_credentials_only(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        config()->set('agentic.widget.broadcast.driver', 'pusher');
        config()->set('agentic.widget.broadcast.pusher', [
            'app_id' => 'app-id',
            'key' => 'pusher-key',
            'secret' => 'pusher-secret',
            'cluster' => 'eu',
        ]);

        $this->getJson('/api/agentic/widget/config?agent=support')
            ->assertOk()
            ->assertJsonPath('data.realtime.driver', 'pusher')
            ->assertJsonPath('data.realtime.pusher.key', 'pusher-key')
            ->assertJsonPath('data.realtime.pusher.cluster', 'eu')
            ->assertJsonMissingPath('data.realtime.pusher.secret')
            ->assertJsonMissingPath('data.realtime.polling');
    }

    public function test_widget_messages_return_pending_when_async_replies_enabled(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        config()->set('agentic.widget.async_replies', true);
        config()->set('agentic.widget.message_batch.window_ms', 0);

        $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Hello',
        ], [
            'X-Agentic-Guest-Id' => 'guest-async-test',
        ])
            ->assertOk()
            ->assertJsonPath('data.pending', true)
            ->assertJsonPath('data.conversation_id', fn ($id) => is_string($id) && $id !== '')
            ->assertJsonMissingPath('data.text');

        Queue::assertPushed(ProcessWidgetMessageJob::class);
    }

    public function test_widget_batches_rapid_messages_when_batch_window_enabled(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        config()->set('agentic.widget.async_replies', true);
        config()->set('agentic.widget.message_batch.window_ms', 3000);
        config()->set('agentic.widget.message_batch.max_ms', 10_000);

        $headers = ['X-Agentic-Guest-Id' => 'guest-batch-test'];

        $first = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'menu',
        ], $headers)->assertOk()->json('data.conversation_id');

        $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'conversation_id' => $first,
            'message' => 'delivery',
        ], $headers)->assertOk()->assertJsonPath('data.batched', true);

        $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'conversation_id' => $first,
            'message' => 'Tanta',
        ], $headers)->assertOk()->assertJsonPath('data.batched', true);

        $this->assertSame(3, ConversationMessage::query()->where('role', 'user')->count());
        Queue::assertPushed(ProcessWidgetBatchedAgentTurnJob::class, 3);
        Queue::assertNotPushed(ProcessWidgetMessageJob::class);
    }

    public function test_widget_message_without_conversation_id_starts_a_new_thread(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        config()->set('agentic.widget.async_replies', true);

        $headers = ['X-Agentic-Guest-Id' => 'guest-new-thread'];

        $first = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'First thread',
        ], $headers)->assertOk()->json('data.conversation_id');

        $second = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Second thread',
        ], $headers)->assertOk()->json('data.conversation_id');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, Conversation::query()->where('agent', 'support')->where('user_id', 'guest:guest-new-thread')->count());
    }

    public function test_async_is_default_for_pusher_and_broadcasts_typing(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        config()->set('agentic.widget.async_replies', null);
        config()->set('agentic.widget.broadcast.driver', 'pusher');

        $this->assertTrue(WidgetReplyDelivery::isAsync());

        $response = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Hi',
        ], [
            'X-Agentic-Guest-Id' => 'guest-typing-test',
        ])->assertOk()->assertJsonPath('data.pending', true);

        $conversationId = $response->json('data.conversation_id');

        $this->assertTrue(
            BroadcastEvent::query()
                ->where('event', 'assistant.typing')
                ->where('channel', WidgetReplyDelivery::conversationChannel($conversationId))
                ->exists(),
        );

        Queue::assertPushed(ProcessWidgetMessageJob::class);
    }

    public function test_widget_messages_are_paginated_newest_page_first(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $conversation = Conversation::query()->create([
            'uuid' => (string) str()->uuid(),
            'agent' => 'support',
            'user_id' => 'guest:history-guest',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            ConversationMessage::query()->create([
                'uuid' => (string) str()->uuid(),
                'conversation_id' => $conversation->id,
                'role' => $i % 2 === 0 ? 'assistant' : 'user',
                'content_html' => "Message {$i}",
                'format' => 'html',
            ]);
        }

        $this->getJson(
            '/api/agentic/widget/conversations/'.$conversation->uuid.'/messages?limit=2',
            ['X-Agentic-Guest-Id' => 'history-guest'],
        )
            ->assertOk()
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.html', 'Message 4')
            ->assertJsonPath('data.1.html', 'Message 5');

        $before = $this->getJson(
            '/api/agentic/widget/conversations/'.$conversation->uuid.'/messages?limit=2&before=4',
            ['X-Agentic-Guest-Id' => 'history-guest'],
        )
            ->assertOk()
            ->assertJsonPath('data.0.html', 'Message 2')
            ->assertJsonPath('data.1.html', 'Message 3');
    }

    public function test_widget_conversations_include_preview_and_last_message_at(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $conversation = Conversation::query()->create([
            'uuid' => (string) str()->uuid(),
            'agent' => 'support',
            'user_id' => 'guest:list-guest',
        ]);

        ConversationMessage::query()->create([
            'uuid' => (string) str()->uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content_html' => 'Need a refund',
            'format' => 'html',
        ]);

        $response = $this->getJson('/api/agentic/widget/conversations?agent=support', [
            'X-Agentic-Guest-Id' => 'list-guest',
        ])->assertOk();

        $response
            ->assertJsonPath('data.0.id', $conversation->uuid)
            ->assertJsonPath('data.0.preview', 'Need a refund');

        $this->assertNotEmpty($response->json('data.0.last_message_at'));
    }
}
