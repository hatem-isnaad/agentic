<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\ConversationMessageRepository;
use Agentic\Conversation\ConversationTurn;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;

final class EloquentConversationMessageRepository implements ConversationMessageRepository
{
    public function priorTurns(string $conversationId, int $maxMessages): array
    {
        if ($maxMessages <= 0) {
            return [];
        }

        $conversation = Conversation::query()->where('uuid', $conversationId)->first();
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

        $turns = [];
        foreach ($rows as $row) {
            $text = trim(strip_tags((string) $row->content_html));
            if ($text === '') {
                continue;
            }
            $turns[] = new ConversationTurn($row->role, $text);
        }

        return $turns;
    }
}
