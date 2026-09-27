<?php

namespace Agentic\Contracts\Repositories;

use Agentic\Memory\MemoryRecord;

interface MemoryRepository
{
    public function remember(MemoryRecord $record): MemoryRecord;

    public function forget(int $id): bool;

    public function forgetKey(string $scope, string $scopeKey, string $key, ?string $agentSlug = null): bool;

    /**
     * @param  list<string>  $scopes
     * @return list<MemoryRecord>
     */
    public function recall(
        array $scopes,
        string $scopeKey,
        ?string $agentSlug = null,
        int $limit = 20,
    ): array;
}
