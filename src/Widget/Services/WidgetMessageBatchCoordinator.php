<?php

namespace Agentic\Widget\Services;

use Agentic\Jobs\ProcessWidgetBatchedAgentTurnJob;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Widget\Support\WidgetMessageBatchSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class WidgetMessageBatchCoordinator
{
    private const CACHE_PREFIX = 'agentic:widget:message-batch:';

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function schedule(string $agentSlug, string $conversationId, array $metadata = []): void
    {
        $windowMs = WidgetMessageBatchSettings::windowMs();
        if ($windowMs <= 0) {
            return;
        }

        $now = now();
        $key = self::CACHE_PREFIX.$conversationId;
        $state = Cache::get($key);
        $burstStart = is_array($state) && isset($state['burst_start'])
            ? (int) $state['burst_start']
            : $now->timestamp;

        $maxMs = WidgetMessageBatchSettings::maxMs();
        $deadline = $burstStart + (int) floor($maxMs / 1000);
        $flushAt = min($now->copy()->addMilliseconds($windowMs)->timestamp, $deadline);

        $token = (string) Str::uuid();
        Cache::put($key, [
            'burst_start' => $burstStart,
            'flush_at' => $flushAt,
            'token' => $token,
        ], max(120, (int) ceil($maxMs / 1000) + 60));

        $delaySeconds = max(0, $flushAt - $now->timestamp);
        ProcessWidgetBatchedAgentTurnJob::dispatch($agentSlug, $conversationId, $metadata, $flushAt, $token)
            ->delay($delaySeconds > 0 ? $now->copy()->addSeconds($delaySeconds) : null);
    }

    public function shouldFlush(string $conversationId, int $expectedFlushAt, string $token): bool
    {
        $state = Cache::get(self::CACHE_PREFIX.$conversationId);
        if (! is_array($state)) {
            return true;
        }

        if (($state['token'] ?? '') !== $token) {
            return false;
        }

        return now()->timestamp >= $expectedFlushAt;
    }

    public function forget(string $conversationId): void
    {
        Cache::forget(self::CACHE_PREFIX.$conversationId);
    }

    /**
     * @return array{message: string, attachments: list<array<string, mixed>>}|null
     */
    public function composePendingUserTurn(string $conversationUuid): ?array
    {
        $conversation = Conversation::query()->where('uuid', $conversationUuid)->first();
        if ($conversation === null) {
            return null;
        }

        $lastAssistantId = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->max('id');

        $query = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', 'user')
            ->orderBy('id');

        if ($lastAssistantId !== null) {
            $query->where('id', '>', $lastAssistantId);
        }

        $messages = $query->get();
        if ($messages->isEmpty()) {
            return null;
        }

        $lines = [];
        $attachments = [];

        foreach ($messages as $message) {
            $text = trim(html_entity_decode(strip_tags((string) $message->content_html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text !== '') {
                $lines[] = $text;
            }

            $meta = is_array($message->metadata) ? $message->metadata : [];
            $files = is_array($meta['attachments'] ?? null) ? $meta['attachments'] : [];
            foreach ($files as $file) {
                if (is_array($file)) {
                    $attachments[] = $file;
                }
            }
        }

        if ($lines === [] && $attachments === []) {
            return null;
        }

        return [
            'message' => implode("\n", $lines),
            'attachments' => $attachments,
        ];
    }
}
