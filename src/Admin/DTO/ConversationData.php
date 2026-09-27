<?php

namespace Agentic\Admin\DTO;

use Agentic\Conversation\Conversation;

final readonly class ConversationData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $agent,
        public ?string $sdkConversationId,
        public string|int|null $userId,
        public string|int|null $tenantId,
        public array $metadata,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromConversation(Conversation $conversation): self
    {
        return new self(
            id: $conversation->id,
            agent: $conversation->agent,
            sdkConversationId: $conversation->sdkConversationId,
            userId: $conversation->userId,
            tenantId: $conversation->tenantId,
            metadata: $conversation->metadata,
            createdAt: $conversation->createdAt,
            updatedAt: $conversation->updatedAt,
        );
    }
}
