<?php

namespace Agentic\Exceptions;

final class ConversationNotFoundException extends AgenticException
{
    public function __construct(string $identifier)
    {
        parent::__construct("Conversation [{$identifier}] was not found.");
    }
}
