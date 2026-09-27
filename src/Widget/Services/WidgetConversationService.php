<?php

namespace Agentic\Widget\Services;

use Agentic\Conversation\ConversationManager;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Widget\DTO\WidgetIdentity;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class WidgetConversationService
{
    public function __construct(
        private ConversationManager $conversations,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAgent(string $agentSlug, WidgetIdentity $identity): array
    {
        $userId = $identity->conversationUserId();

        return Conversation::query()
            ->where('agent', $agentSlug)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->with('latestChatMessage')
            ->latest('updated_at')
            ->latest('id')
            ->limit((int) config('agentic.widget.conversation.max_open_per_user', 20))
            ->get()
            ->map(fn (Conversation $row) => $this->serializeConversation($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function create(string $agentSlug, WidgetIdentity $identity, array $metadata = []): array
    {
        $conversation = $this->conversations->start(
            agent: $agentSlug,
            userId: $identity->conversationUserId(),
            tenantId: $identity->tenantId,
            metadata: $metadata,
        );

        return $this->serializeConversation(
            Conversation::query()->where('uuid', $conversation->id)->firstOrFail(),
        );
    }

    /**
     * Paginated messages (newest page first). Pass {@see $beforeCursor} to load older messages.
     *
     * @return array{messages: list<array<string, mixed>>, meta: array{has_more: bool, next_before: int|null}}
     */
    public function messagesPage(
        string $conversationUuid,
        WidgetIdentity $identity,
        int $limit = 20,
        ?int $beforeCursor = null,
    ): array {
        $conversation = $this->resolveConversationForIdentity($conversationUuid, $identity);

        $max = max(1, (int) config('agentic.widget.history.max_page_size', 50));
        $limit = max(1, min($max, $limit));

        $query = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', '!=', 'system')
            ->when($beforeCursor !== null, fn ($q) => $q->where('id', '<', $beforeCursor))
            ->orderByDesc('id')
            ->limit($limit);

        $rows = $query->get()->reverse()->values();

        $oldestId = $rows->first()?->id;
        $hasMore = false;
        if ($oldestId !== null) {
            $hasMore = ConversationMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('role', '!=', 'system')
                ->where('id', '<', $oldestId)
                ->exists();
        }

        return [
            'messages' => $rows
                ->map(fn (ConversationMessage $message) => $this->serializeMessage($message))
                ->all(),
            'meta' => [
                'has_more' => $hasMore,
                'next_before' => $hasMore ? $oldestId : null,
            ],
        ];
    }

    private function resolveConversationForIdentity(string $conversationUuid, WidgetIdentity $identity): Conversation
    {
        $conversation = Conversation::query()->where('uuid', $conversationUuid)->first();

        if ($conversation === null) {
            throw new ModelNotFoundException();
        }

        $userId = $identity->conversationUserId();
        if ($userId !== null && $conversation->user_id !== null && $conversation->user_id !== $userId) {
            throw new ModelNotFoundException();
        }

        return $conversation;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(ConversationMessage $message): array
    {
        $blocks = is_array($message->metadata['blocks'] ?? null) ? $message->metadata['blocks'] : null;

        return [
            'id' => $message->uuid,
            'cursor' => (int) $message->id,
            'role' => $message->role,
            'html' => $message->content_html,
            'format' => $message->format,
            'blocks' => $blocks,
            'created_at' => optional($message->created_at)?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversation(Conversation $row): array
    {
        $latest = $row->latestChatMessage;
        $preview = is_string($latest?->content_html) ? trim(strip_tags($latest->content_html)) : '';
        $lastAt = $latest?->created_at ?? $row->updated_at ?? $row->created_at;

        return [
            'id' => $row->uuid,
            'agent' => $row->agent,
            'user_id' => $row->user_id,
            'tenant_id' => $row->tenant_id,
            'metadata' => $row->metadata ?? [],
            'preview' => mb_substr($preview, 0, 120),
            'last_message_at' => optional($lastAt)?->toISOString(),
            'created_at' => optional($row->created_at)?->toISOString(),
            'updated_at' => optional($row->updated_at)?->toISOString(),
        ];
    }
}
