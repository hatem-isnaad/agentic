<?php

namespace Agentic\Widget\Services;

use Agentic\Conversation\ConversationManager;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
final class WidgetConversationService
{
    public function __construct(
        private ConversationManager $conversations,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAgent(string $agentSlug, ?string $guestId, ?int $userId): array
    {
        $identity = $userId !== null ? (string) $userId : ($guestId !== null ? 'guest:'.$guestId : null);

        return Conversation::query()
            ->where('agent', $agentSlug)
            ->when($identity !== null, fn ($q) => $q->where('user_id', $identity))
            ->latest('id')
            ->limit((int) config('agentic.widget.conversation.max_open_per_user', 10))
            ->get()
            ->map(fn (Conversation $row) => $this->serializeConversation($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function create(string $agentSlug, ?string $guestId, ?int $userId, ?string $tenantId, array $metadata = []): array
    {
        $identity = $userId !== null ? (string) $userId : ($guestId !== null ? 'guest:'.$guestId : null);

        $conversation = $this->conversations->start(
            agent: $agentSlug,
            userId: $identity,
            tenantId: $tenantId,
            metadata: $metadata,
        );

        return $this->serializeConversation(
            Conversation::query()->where('uuid', $conversation->id)->firstOrFail(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function messages(string $conversationUuid): array
    {
        $conversation = Conversation::query()->where('uuid', $conversationUuid)->firstOrFail();

        return ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', '!=', 'system')
            ->orderBy('id')
            ->get()
            ->map(function (ConversationMessage $message): array {
                $blocks = is_array($message->metadata['blocks'] ?? null) ? $message->metadata['blocks'] : null;

                return [
                    'id' => $message->uuid,
                    'role' => $message->role,
                    'html' => $message->content_html,
                    'format' => $message->format,
                    'blocks' => $blocks,
                    'tokens_in' => $message->tokens_in,
                    'tokens_out' => $message->tokens_out,
                    'tokens_total' => $message->tokens_total,
                    'locale' => $message->locale,
                    'created_at' => optional($message->created_at)?->toISOString(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversation(Conversation $row): array
    {
        return [
            'id' => $row->uuid,
            'agent' => $row->agent,
            'user_id' => $row->user_id,
            'tenant_id' => $row->tenant_id,
            'metadata' => $row->metadata ?? [],
            'created_at' => optional($row->created_at)?->toISOString(),
            'updated_at' => optional($row->updated_at)?->toISOString(),
        ];
    }
}
