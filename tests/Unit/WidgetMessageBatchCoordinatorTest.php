<?php

namespace Agentic\Tests\Unit;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Agentic\Widget\Services\WidgetMessageBatchCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class WidgetMessageBatchCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.widget.message_batch.window_ms', 3000);
        $app['config']->set('agentic.widget.message_batch.max_ms', 10_000);
        $app['config']->set('agentic.widget.async_replies', true);
    }

    public function test_compose_pending_user_turn_joins_messages_since_last_assistant(): void
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
            'user_id' => 'guest-1',
        ]);

        ConversationMessage::query()->create([
            'uuid' => (string) str()->uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content_html' => 'Hi',
            'format' => 'html',
        ]);

        foreach (['menu', 'delivery', 'Tanta'] as $line) {
            ConversationMessage::query()->create([
                'uuid' => (string) str()->uuid(),
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content_html' => e($line),
                'format' => 'html',
            ]);
        }

        $composed = app(WidgetMessageBatchCoordinator::class)->composePendingUserTurn($conversation->uuid);

        $this->assertNotNull($composed);
        $this->assertSame("menu\ndelivery\nTanta", $composed['message']);
    }

    public function test_should_flush_rejects_stale_schedule_token(): void
    {
        $conversationId = (string) str()->uuid();
        Cache::put('agentic:widget:message-batch:'.$conversationId, [
            'burst_start' => now()->timestamp,
            'flush_at' => now()->timestamp,
            'token' => 'current',
        ], 60);

        $batch = app(WidgetMessageBatchCoordinator::class);

        $this->assertFalse($batch->shouldFlush($conversationId, now()->timestamp, 'old'));
        $this->assertTrue($batch->shouldFlush($conversationId, now()->timestamp, 'current'));
    }
}
