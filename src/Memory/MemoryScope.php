<?php

namespace Agentic\Memory;

final class MemoryScope
{
    public const User = 'user';

    public const Agent = 'agent';

    public const Conversation = 'conversation';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::User, self::Agent, self::Conversation];
    }
}
