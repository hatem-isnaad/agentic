<?php

namespace Agentic\Memory;

use Agentic\Contracts\Repositories\MemoryRepository;
use Agentic\Context\RuntimeContext;
use DateInterval;
use DateTimeImmutable;

final class MemoryManager
{
    public function __construct(
        private MemoryRepository $memories,
        private MemoryContextResolver $context,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function remember(
        string $scope,
        string $scopeKey,
        string $key,
        string $content,
        ?string $agentSlug = null,
        int $importance = 5,
        array $metadata = [],
        ?\DateTimeInterface $expiresAt = null,
    ): MemoryRecord {
        return $this->memories->remember(new MemoryRecord(
            id: null,
            scope: $scope,
            scopeKey: $scopeKey,
            agentSlug: $agentSlug,
            key: $key,
            content: $content,
            importance: $importance,
            metadata: $metadata,
            expiresAt: $expiresAt ?? $this->defaultExpiry(),
        ));
    }

    public function forget(int $id): bool
    {
        return $this->memories->forget($id);
    }

    public function forgetKey(string $scope, string $scopeKey, string $key, ?string $agentSlug = null): bool
    {
        return $this->memories->forgetKey($scope, $scopeKey, $key, $agentSlug);
    }

    /**
     * @return list<MemoryRecord>
     */
    public function list(string $scope, string $scopeKey, ?string $agentSlug = null, int $limit = 20): array
    {
        return $this->memories->recall([$scope], $scopeKey, $agentSlug, $limit);
    }

    /**
     * @return list<MemoryRecord>
     */
    public function recallForRuntime(RuntimeContext $runtime, ?string $agentSlug = null): array
    {
        if (! (bool) config('agentic.memory.enabled', true)) {
            return [];
        }

        $limit = max(1, (int) config('agentic.memory.max_context_entries', 20));
        $resolved = $this->context->scopes($runtime, $agentSlug);
        $records = [];
        $seen = [];

        foreach ($resolved as $entry) {
            $batch = $this->memories->recall(
                [$entry['scope']],
                $entry['scope_key'],
                $agentSlug,
                $limit,
            );

            foreach ($batch as $record) {
                $signature = $record->scope.'|'.$record->scopeKey.'|'.$record->key;

                if (isset($seen[$signature])) {
                    continue;
                }

                $seen[$signature] = true;
                $records[] = $record;
            }
        }

        usort($records, fn (MemoryRecord $a, MemoryRecord $b): int => $b->importance <=> $a->importance);

        return array_slice($records, 0, $limit);
    }

    private function defaultExpiry(): ?DateTimeImmutable
    {
        $days = config('agentic.memory.default_ttl_days');

        if (! is_numeric($days) || (int) $days <= 0) {
            return null;
        }

        return (new DateTimeImmutable())->add(new DateInterval('P'.(int) $days.'D'));
    }
}
