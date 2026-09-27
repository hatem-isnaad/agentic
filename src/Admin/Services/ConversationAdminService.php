<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\ConversationData;
use Agentic\Contracts\Repositories\ConversationRepository;

final class ConversationAdminService
{
    public function __construct(
        private ConversationRepository $conversations,
    ) {}

    /**
     * @return list<ConversationData>
     */
    public function list(int $limit = 50): array
    {
        return array_map(
            fn ($conversation) => ConversationData::fromConversation($conversation),
            $this->conversations->recent($limit),
        );
    }

    public function find(string $id): ?ConversationData
    {
        $conversation = $this->conversations->find($id);

        return $conversation ? ConversationData::fromConversation($conversation) : null;
    }
}
