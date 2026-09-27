<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Conversation\Conversation;

final class InMemoryConversationRepository implements ConversationRepository
{
    /** @var array<string, Conversation> */
    private array $conversations = [];

    public function store(Conversation $conversation): Conversation
    {
        $this->conversations[$conversation->id] = $conversation;

        return $conversation;
    }

    public function find(string $id): ?Conversation
    {
        return $this->conversations[$id] ?? null;
    }

    public function update(Conversation $conversation): Conversation
    {
        $this->conversations[$conversation->id] = $conversation;

        return $conversation;
    }

    public function recent(int $limit = 50): array
    {
        $items = array_values($this->conversations);

        if (count($items) <= $limit) {
            return $items;
        }

        return array_slice($items, -$limit);
    }

    public function findLatestFor(string $agent, string|int|null $userId = null, string|int|null $tenantId = null): ?Conversation
    {
        $matches = array_values(array_filter(
            $this->conversations,
            fn (Conversation $conversation) => $conversation->agent === $agent
                && $conversation->userId == $userId
                && $conversation->tenantId == $tenantId,
        ));

        return $matches === [] ? null : $matches[array_key_last($matches)];
    }
}
