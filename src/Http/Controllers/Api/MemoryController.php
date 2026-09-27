<?php

namespace Agentic\Http\Controllers\Api;

use Agentic\Memory\MemoryManager;
use Agentic\Memory\MemoryScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MemoryController
{
    public function __construct(
        private MemoryManager $memories,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', Rule::in(MemoryScope::all())],
            'scope_key' => ['required', 'string', 'max:191'],
            'agent_slug' => ['nullable', 'string', 'max:191'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $records = $this->memories->list(
            $validated['scope'],
            $validated['scope_key'],
            $validated['agent_slug'] ?? null,
            (int) ($validated['limit'] ?? 20),
        );

        return response()->json([
            'data' => array_map(fn ($record) => $record->toArray(), $records),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', Rule::in(MemoryScope::all())],
            'scope_key' => ['required', 'string', 'max:191'],
            'key' => ['required', 'string', 'max:191'],
            'content' => ['required', 'string'],
            'agent_slug' => ['nullable', 'string', 'max:191'],
            'importance' => ['nullable', 'integer', 'min:1', 'max:10'],
            'metadata' => ['nullable', 'array'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $record = $this->memories->remember(
            scope: $validated['scope'],
            scopeKey: $validated['scope_key'],
            key: $validated['key'],
            content: $validated['content'],
            agentSlug: $validated['agent_slug'] ?? null,
            importance: (int) ($validated['importance'] ?? 5),
            metadata: $validated['metadata'] ?? [],
            expiresAt: isset($validated['expires_at'])
                ? new \DateTimeImmutable($validated['expires_at'])
                : null,
        );

        return response()->json(['data' => $record->toArray()], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        if (! $this->memories->forget($id)) {
            return response()->json(['message' => 'Memory not found.'], 404);
        }

        return response()->json(['deleted' => true]);
    }
}
