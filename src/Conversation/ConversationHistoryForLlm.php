<?php

namespace Agentic\Conversation;

use Agentic\Context\LlmInputCompactor;
use Agentic\Contracts\Repositories\ConversationMessageRepository;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;

final class ConversationHistoryForLlm
{
    public function __construct(
        private ConversationMessageRepository $messages,
        private LlmInputCompactor $compactor,
    ) {}

    /**
     * @return list<UserMessage|AssistantMessage>
     */
    public function priorMessages(string $conversationId, int $maxMessages): array
    {
        $turns = $this->messages->priorTurns($conversationId, $maxMessages);
        $texts = $this->compactor->history(array_map(
            fn (ConversationTurn $turn): string => $turn->text,
            $turns,
        ));
        $turns = array_slice($turns, max(0, count($turns) - count($texts)));

        $history = [];

        foreach ($texts as $index => $text) {
            if ($text === '') {
                continue;
            }

            $role = $turns[$index]->role ?? 'assistant';
            $history[] = $role === 'user' ? new UserMessage($text) : new AssistantMessage($text);
        }

        return $history;
    }
}
