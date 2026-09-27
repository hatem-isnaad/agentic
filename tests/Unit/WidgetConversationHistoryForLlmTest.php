<?php

namespace Agentic\Tests\Unit;

use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Agentic\Conversation\ConversationHistoryForLlm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;

final class WidgetConversationHistoryForLlmTest extends TestCase
{
    use RefreshDatabase;

    public function test_prior_messages_exclude_current_user_turn(): void
    {
        $conversation = Conversation::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'agent' => 'support',
            'user_id' => 'guest:test',
        ]);

        ConversationMessage::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content_html' => 'First',
            'format' => 'html',
        ]);
        ConversationMessage::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content_html' => 'Reply one',
            'format' => 'html',
        ]);
        ConversationMessage::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
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
}
