<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Conversation\ConversationTurn;

interface ConversationMessageRepository
{
    /**
     * Prior user/assistant turns for the LLM, excluding the current user message.
     *
     * @return list<ConversationTurn>
     */
    public function priorTurns(string $conversationId, int $maxMessages): array;
}
