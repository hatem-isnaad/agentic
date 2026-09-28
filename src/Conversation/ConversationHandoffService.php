<?php

namespace Agentic\Conversation;

use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Exceptions\ConversationNotFoundException;

final class ConversationHandoffService
{
    public const None = 'none';

    public const Requested = 'requested';

    public const Taken = 'taken';

    public function __construct(private ConversationRepository $conversations) {}

    public function request(string $conversationId, string $reason = '', ?string $by = null): Conversation
    {
        return $this->write($conversationId, self::Requested, $by, $reason);
    }

    public function take(string $conversationId, string $by): Conversation
    {
        return $this->write($conversationId, self::Taken, $by, null);
    }

    public function release(string $conversationId, ?string $by = null): Conversation
    {
        return $this->write($conversationId, self::None, $by, null);
    }

    public function status(?Conversation $conversation): string
    {
        $handoff = is_array($conversation?->metadata['handoff'] ?? null) ? $conversation->metadata['handoff'] : [];

        return (string) ($handoff['status'] ?? self::None);
    }

    public function isHuman(Conversation $conversation): bool
    {
        $status = $this->status($conversation);

        return $status === self::Taken || $status === self::Requested;
    }

    public function isHumanId(string $conversationId): bool
    {
        $conversation = $this->conversations->find($conversationId);

        return $conversation !== null && $this->isHuman($conversation);
    }

    public function isStaffChatId(string $conversationId): bool
    {
        $conversation = $this->conversations->find($conversationId);

        return $conversation !== null && $this->status($conversation) === self::Taken;
    }

    /**
     * @return array{active: bool, staff_chat: bool, status: string}
     */
    public function widgetPayloadForId(string $conversationId): array
    {
        $conversation = $this->conversations->find($conversationId);
        if ($conversation === null) {
            return [
                'active' => false,
                'staff_chat' => false,
                'status' => self::None,
            ];
        }

        $status = $this->status($conversation);

        return [
            'active' => $this->isHuman($conversation),
            'staff_chat' => $status === self::Taken,
            'status' => $status,
        ];
    }

    /**
     * @return list<Conversation>
     */
    public function inbox(int $limit = 100): array
    {
        $rows = [];

        foreach ($this->conversations->recent(max(50, min(400, $limit))) as $conversation) {
            $rows[] = $conversation;
        }

        usort($rows, function (Conversation $a, Conversation $b): int {
            $human = ((int) $this->isHuman($b)) <=> ((int) $this->isHuman($a));
            if ($human !== 0) {
                return $human;
            }

            return strcmp((string) $b->updatedAt, (string) $a->updatedAt);
        });

        return $rows;
    }

    private function write(string $conversationId, string $status, ?string $by, ?string $reason): Conversation
    {
        $conversation = $this->conversations->find($conversationId);
        if ($conversation === null) {
            throw new ConversationNotFoundException($conversationId);
        }

        $metadata = $conversation->metadata;
        $metadata['handoff'] = array_filter([
            'status' => $status,
            'by' => $by,
            'reason' => $reason !== null && $reason !== '' ? $reason : ($metadata['handoff']['reason'] ?? null),
            'at' => now()->toIso8601String(),
        ], fn ($value) => $value !== null && $value !== '');

        $conversation->metadata = $metadata;
        $conversation->updatedAt = now()->toIso8601String();

        return $this->conversations->update($conversation);
    }
}
