<?php

namespace Agentic\Conversation;

use Agentic\Contracts\Repositories\ConversationMessageRepository;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;

final class ConversationHistoryForLlm
{
    public function __construct(private ConversationMessageRepository $messages) {}

    /**
     * @return list<UserMessage|AssistantMessage>
     */
    public function priorMessages(string $conversationId, int $maxMessages): array
    {
        $history = [];

        foreach ($this->messages->priorTurns($conversationId, $maxMessages) as $turn) {
            $history[] = $turn->role === 'user' ? new UserMessage($turn->text) : new AssistantMessage($turn->text);
        }

        return $history;
    }
}
