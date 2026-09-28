<?php

namespace Agentic\Memory;

use Agentic\Context\RuntimeContext;

final class MemoryContextResolver
{
    /**
     * @return list<array{scope: string, scope_key: string}>
     */
    public function scopes(RuntimeContext $runtime, ?string $agentSlug = null): array
    {
        $scopes = [];

        $conversation = $runtime->conversation();
        $conversationId = is_object($conversation) && isset($conversation->id)
            ? (string) $conversation->id
            : (is_string($runtime->get('conversation_id')) ? $runtime->get('conversation_id') : null);

        if ($conversationId !== null && $conversationId !== '') {
            $scopes[] = ['scope' => MemoryScope::Conversation, 'scope_key' => $conversationId];
        }

        $user = $runtime->user();
        $userKey = $this->stringKey($user) ?? $this->stringKey($runtime->get('user_id'));

        if ($userKey !== null) {
            $scopes[] = ['scope' => MemoryScope::User, 'scope_key' => $userKey];
        }

        if (is_string($agentSlug) && $agentSlug !== '') {
            $scopes[] = ['scope' => MemoryScope::Agent, 'scope_key' => $agentSlug];
        }

        return $scopes;
    }

    private function stringKey(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_object($value) && isset($value->id)) {
            return (string) $value->id;
        }

        return null;
    }
}
