<?php

namespace Agentic\Conversation;

/**
 * Agentic-level conversation record.
 *
 * Provider-level message history remains owned by Laravel AI SDK.
 */
final class Conversation
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $agent,
        public ?string $sdkConversationId = null,
        public string|int|null $userId = null,
        public string|int|null $tenantId = null,
        public array $metadata = [],
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
