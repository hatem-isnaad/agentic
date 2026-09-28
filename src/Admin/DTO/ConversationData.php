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
        public array $metadata,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'agent' => $this->agent,
            'sdk_conversation_id' => $this->sdkConversationId,
            'user_id' => $this->userId,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromConversation(Conversation $conversation): self
    {
        return new self(
            id: $conversation->id,
            agent: $conversation->agent,
            sdkConversationId: $conversation->sdkConversationId,
            userId: $conversation->userId,
            metadata: $conversation->metadata,
            createdAt: $conversation->createdAt,
            updatedAt: $conversation->updatedAt,
        );
    }
}
