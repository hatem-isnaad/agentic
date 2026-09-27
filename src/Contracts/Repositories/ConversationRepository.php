<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Conversation\Conversation;

interface ConversationRepository
{
    public function store(Conversation $conversation): Conversation;

    public function find(string $id): ?Conversation;

    public function update(Conversation $conversation): Conversation;

    public function findLatestFor(string $agent, string|int|null $userId = null, string|int|null $tenantId = null): ?Conversation;

    /**
     * @return list<Conversation>
     */
    public function recent(int $limit = 50): array;
}
