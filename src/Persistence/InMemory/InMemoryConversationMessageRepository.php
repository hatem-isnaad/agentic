<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\ConversationMessageRepository;

final class InMemoryConversationMessageRepository implements ConversationMessageRepository
{
    public function priorTurns(string $conversationId, int $maxMessages): array
    {
        return [];
    }
}
