<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Conversation\Conversation as ConversationDto;
use Agentic\Models\Conversation;

final class EloquentConversationRepository implements ConversationRepository
{
    public function store(ConversationDto $conversation): ConversationDto
    {
        $model = Conversation::query()->create([
            'uuid' => $conversation->id,
            'agent' => $conversation->agent,
            'sdk_conversation_id' => $conversation->sdkConversationId,
            'user_id' => $conversation->userId,
            'tenant_id' => $conversation->tenantId,
            'metadata' => $conversation->metadata,
        ]);

        return $this->toDto($model);
    }

    public function find(string $id): ?ConversationDto
    {
        $model = Conversation::query()->where('uuid', $id)->first();

        return $model ? $this->toDto($model) : null;
    }

    public function update(ConversationDto $conversation): ConversationDto
    {
        $model = Conversation::query()->where('uuid', $conversation->id)->firstOrFail();

        $model->fill([
            'sdk_conversation_id' => $conversation->sdkConversationId,
            'user_id' => $conversation->userId,
            'tenant_id' => $conversation->tenantId,
            'metadata' => $conversation->metadata,
        ])->save();

        return $this->toDto($model->fresh());
    }

    public function findLatestFor(string $agent, string|int|null $userId = null, string|int|null $tenantId = null): ?ConversationDto
    {
        $model = Conversation::query()
            ->where('agent', $agent)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest('id')
            ->first();

        return $model ? $this->toDto($model) : null;
    }

    private function toDto(Conversation $model): ConversationDto
    {
        return new ConversationDto(
            id: $model->uuid,
            agent: $model->agent,
            sdkConversationId: $model->sdk_conversation_id,
            userId: $model->user_id,
            tenantId: $model->tenant_id,
            metadata: $model->metadata ?? [],
            createdAt: optional($model->created_at)?->toISOString(),
            updatedAt: optional($model->updated_at)?->toISOString(),
        );
    }
}
