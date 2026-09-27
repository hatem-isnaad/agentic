<?php

namespace Agentic\Conversation;

use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;

/**
 * Prior turns for the Laravel AI SDK (excludes the current user message).
 */
final class ConversationHistoryForLlm
{
    /**
     * @return list<UserMessage|AssistantMessage>
     */
    public function priorMessages(string $conversationUuid, int $maxMessages): array
    {
        if ($maxMessages <= 0 || ! Schema::hasTable((new Conversation)->getTable())) {
            return [];
        }

        $conversation = Conversation::query()->where('uuid', $conversationUuid)->first();

        if ($conversation === null) {
            return [];
        }

        $rows = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('id')
            ->limit($maxMessages + 1)
            ->get()
            ->reverse()
            ->values();

        if ($rows->isNotEmpty() && $rows->last()->role === 'user') {
            $rows = $rows->slice(0, $rows->count() - 1);
        }

        if ($rows->count() > $maxMessages) {
            $rows = $rows->slice($rows->count() - $maxMessages)->values();
        }

        $messages = [];

        foreach ($rows as $row) {
            $text = trim(strip_tags((string) $row->content_html));

            if ($text === '') {
                continue;
            }

            $messages[] = $row->role === 'user'
                ? new UserMessage($text)
                : new AssistantMessage($text);
        }

        return $messages;
    }
}
