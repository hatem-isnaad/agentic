<?php

namespace Agentic\Persistence\InMemory;

use Agentic\Contracts\Repositories\MemoryRepository;
use Agentic\Memory\MemoryRecord;

final class InMemoryMemoryRepository implements MemoryRepository
{
    /** @var array<int, MemoryRecord> */
    private array $records = [];

    private int $nextId = 1;

    public function remember(MemoryRecord $record): MemoryRecord
    {
        foreach ($this->records as $existing) {
            if ($existing->scope === $record->scope
                && $existing->scopeKey === $record->scopeKey
                && $existing->agentSlug === $record->agentSlug
                && $existing->key === $record->key) {
                $updated = new MemoryRecord(
                    id: $existing->id,
                    scope: $record->scope,
                    scopeKey: $record->scopeKey,
                    agentSlug: $record->agentSlug,
                    key: $record->key,
                    content: $record->content,
                    importance: $record->importance,
                    metadata: $record->metadata,
                    expiresAt: $record->expiresAt,
                );
                $this->records[$existing->id] = $updated;

                return $updated;
            }
        }

        $stored = new MemoryRecord(
            id: $this->nextId,
            scope: $record->scope,
            scopeKey: $record->scopeKey,
            agentSlug: $record->agentSlug,
            key: $record->key,
            content: $record->content,
            importance: $record->importance,
            metadata: $record->metadata,
            expiresAt: $record->expiresAt,
        );

        $this->records[$this->nextId] = $stored;
        $this->nextId++;

        return $stored;
    }

    public function forget(int $id): bool
    {
        if (! isset($this->records[$id])) {
            return false;
        }

        unset($this->records[$id]);

        return true;
    }

    public function forgetKey(string $scope, string $scopeKey, string $key, ?string $agentSlug = null): bool
    {
        foreach ($this->records as $id => $record) {
            if ($record->scope === $scope
                && $record->scopeKey === $scopeKey
                && $record->key === $key
                && $record->agentSlug === $agentSlug) {
                unset($this->records[$id]);

                return true;
            }
        }

        return false;
    }

    public function recall(array $scopes, string $scopeKey, ?string $agentSlug = null, int $limit = 20): array
    {
        $now = new \DateTimeImmutable();
        $matches = [];

        foreach ($this->records as $record) {
            if (! in_array($record->scope, $scopes, true) || $record->scopeKey !== $scopeKey) {
                continue;
            }

            if ($record->agentSlug !== null && $record->agentSlug !== $agentSlug) {
                continue;
            }

            if ($record->expiresAt !== null && $record->expiresAt < $now) {
                continue;
            }

            $matches[] = $record;
        }

        usort($matches, function (MemoryRecord $a, MemoryRecord $b): int {
            return [$b->importance, $b->id ?? 0] <=> [$a->importance, $a->id ?? 0];
        });

        return array_slice($matches, 0, max(1, $limit));
    }
}
