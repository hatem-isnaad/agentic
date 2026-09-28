<?php

namespace Agentic\Conversation;

final readonly class ConversationTurn
{
    public function __construct(public string $role, public string $text) {}
}
