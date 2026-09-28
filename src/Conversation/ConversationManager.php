<?php

namespace Agentic\Conversation;

use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Exceptions\ConversationNotFoundException;
use Illuminate\Support\Str;

/**
 * Manages Agentic conversation records and SDK conversation associations.
 */
final class ConversationManager
{
    public function __construct(
        private ConversationRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function start(
        string $agent,
        string|int|null $userId = null,
        ?string $sdkConversationId = null,
        array $metadata = [],
    ): Conversation {
        return $this->repository->store(new Conversation(
            id: (string) Str::uuid(),
            agent: $agent,
            sdkConversationId: $sdkConversationId,
            userId: $userId,
            metadata: $metadata,
            createdAt: now()->toISOString(),
            updatedAt: now()->toISOString(),
        ));
    }

    public function continue(string $id): Conversation
    {
        $conversation = $this->repository->find($id);

        if ($conversation === null) {
            throw new ConversationNotFoundException($id);
        }

        return $conversation;
    }

    public function continueOrStart(
        string $agent,
        ?string $conversationId = null,
        string|int|null $userId = null,
        array $metadata = [],
    ): Conversation {
        if ($conversationId !== null) {
            return $this->continue($conversationId);
        }

        $latest = $this->repository->findLatestFor($agent, $userId);

        return $latest ?? $this->start($agent, $userId, metadata: $metadata);
    }

    public function bindSdkConversation(Conversation $conversation, string $sdkConversationId): Conversation
    {
        $conversation->sdkConversationId = $sdkConversationId;
        $conversation->updatedAt = now()->toISOString();

        return $this->repository->update($conversation);
    }
}
