<?php

namespace Agentic\Persistence\Eloquent;

use Agentic\Contracts\Repositories\MemoryRepository;
use Agentic\Memory\MemoryRecord;
use Agentic\Models\Memory;
use Illuminate\Support\Carbon;

final class EloquentMemoryRepository implements MemoryRepository
{
    public function remember(MemoryRecord $record): MemoryRecord
    {
        $model = Memory::query()->updateOrCreate(
            [
                'scope' => $record->scope,
                'scope_key' => $record->scopeKey,
                'agent_slug' => $record->agentSlug,
                'key' => $record->key,
            ],
            [
                'content' => $record->content,
                'importance' => max(1, min(10, $record->importance)),
                'metadata' => $record->metadata,
                'expires_at' => $record->expiresAt,
            ],
        );

        return $this->toRecord($model);
    }

    public function forget(int $id): bool
    {
        return Memory::query()->whereKey($id)->delete() > 0;
    }

    public function forgetKey(string $scope, string $scopeKey, string $key, ?string $agentSlug = null): bool
    {
        return Memory::query()
            ->where('scope', $scope)
            ->where('scope_key', $scopeKey)
            ->where('key', $key)
            ->when(
                $agentSlug === null,
                fn ($query) => $query->whereNull('agent_slug'),
                fn ($query) => $query->where('agent_slug', $agentSlug),
            )
            ->delete() > 0;
    }

    public function recall(array $scopes, string $scopeKey, ?string $agentSlug = null, int $limit = 20): array
    {
        if ($scopes === []) {
            return [];
        }

        $query = Memory::query()
            ->whereIn('scope', $scopes)
            ->where('scope_key', $scopeKey)
            ->where(function ($builder) use ($agentSlug): void {
                $builder->whereNull('agent_slug');

                if ($agentSlug !== null) {
                    $builder->orWhere('agent_slug', $agentSlug);
                }
            })
            ->where(function ($builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderByDesc('importance')
            ->orderByDesc('updated_at')
            ->limit(max(1, $limit));

        return $query->get()
            ->map(fn (Memory $memory) => $this->toRecord($memory))
            ->all();
    }

    private function toRecord(Memory $memory): MemoryRecord
    {
        return new MemoryRecord(
            id: $memory->id,
            scope: $memory->scope,
            scopeKey: $memory->scope_key,
            agentSlug: $memory->agent_slug,
            key: $memory->key,
            content: $memory->content,
            importance: (int) $memory->importance,
            metadata: is_array($memory->metadata) ? $memory->metadata : [],
            expiresAt: $memory->expires_at,
        );
    }
}
