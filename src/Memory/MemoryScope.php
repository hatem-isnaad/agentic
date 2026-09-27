<?php

namespace Agentic\Memory;

final class MemoryScope
{
    public const User = 'user';

    public const Agent = 'agent';

    public const Conversation = 'conversation';

    public const Tenant = 'tenant';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::User, self::Agent, self::Conversation, self::Tenant];
    }
}
