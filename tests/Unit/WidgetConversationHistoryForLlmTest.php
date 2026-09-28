<?php

namespace Agentic\Tests\Unit;

use Agentic\Context\LlmInputCompactor;
use Agentic\Contracts\Repositories\ConversationMessageRepository;
use Agentic\Conversation\ConversationHistoryForLlm;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;

final class WidgetConversationHistoryForLlmTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('agentic.conversation.driver', 'eloquent');
    }

    public function test_prior_messages_exclude_current_user_turn(): void
    {
        $conversation = Conversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'agent' => 'support',
            'user_id' => 'guest:test',
        ]);

        ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content_html' => 'First',
            'format' => 'html',
        ]);
        ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content_html' => 'Reply one',
            'format' => 'html',
        ]);
        ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content_html' => 'Second',
            'format' => 'html',
        ]);

        $history = app(ConversationHistoryForLlm::class)->priorMessages($conversation->uuid, 10);

        $this->assertCount(2, $history);
        $this->assertInstanceOf(UserMessage::class, $history[0]);
        $this->assertSame('First', $history[0]->content);
        $this->assertInstanceOf(AssistantMessage::class, $history[1]);
        $this->assertSame('Reply one', $history[1]->content);
    }

    public function test_memory_driver_returns_no_eloquent_history(): void
    {
        $this->app['config']->set('agentic.conversation.driver', 'memory');
        $this->app->forgetInstance(ConversationMessageRepository::class);
        $this->app->forgetInstance(ConversationHistoryForLlm::class);

        $history = app(ConversationHistoryForLlm::class)->priorMessages('missing', 10);

        $this->assertSame([], $history);
    }

    public function test_prior_messages_trim_long_assistant_html(): void
    {
        config()->set('agentic.context.compact.enabled', true);
        config()->set('agentic.context.compact.history_message_chars', 80);
        config()->set('agentic.context.compact.history_recent_chars', 80);
        config()->set('agentic.context.compact.history_total_chars', 200);
        $this->app->forgetInstance(ConversationHistoryForLlm::class);
        $this->app->forgetInstance(LlmInputCompactor::class);

        $conversation = Conversation::query()->create([
            'uuid' => (string) Str::uuid(),
            'agent' => 'support',
            'user_id' => 'guest:trim',
        ]);

        ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content_html' => 'get order 1004',
            'format' => 'html',
        ]);
        ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content_html' => '<p>'.str_repeat('Order details table cell ', 80).'</p>',
            'format' => 'html',
        ]);

        $history = app(ConversationHistoryForLlm::class)->priorMessages($conversation->uuid, 10);

        $this->assertCount(2, $history);
        $this->assertStringContainsString('[trimmed]', $history[1]->content);
        $this->assertLessThan(120, mb_strlen($history[1]->content));
    }
}
