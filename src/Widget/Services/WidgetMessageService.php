<?php

namespace Agentic\Widget\Services;

use Agentic\Agent\AgentResolver;
use Agentic\Conversation\ConversationManager;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Runtime\AgentRuntime;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Agentic\Widget\Reply\StructuredReplyBuilder;
use Illuminate\Support\Str;

final class WidgetMessageService
{
    public function __construct(
        private AgentResolver $agents,
        private AgentRuntime $runtime,
        private ConversationManager $conversations,
        private StructuredReplyBuilder $replies,
        private WidgetBroadcastDriver $broadcast,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function send(
        string $agentSlug,
        string $message,
        ?string $conversationId,
        ?string $guestId,
        ?int $userId,
        ?string $tenantId,
        array $metadata = [],
    ): array {
        try {
            $agent = $this->agents->resolve($agentSlug);
        } catch (AgentNotFoundException) {
            return ['success' => false, 'error' => 'Agent not found.'];
        }

        $identity = $userId !== null ? (string) $userId : ($guestId !== null ? 'guest:'.$guestId : null);

        $conversation = $this->conversations->continueOrStart(
            agent: $agentSlug,
            conversationId: $conversationId,
            userId: $identity,
            tenantId: $tenantId,
            metadata: $metadata,
        );

        $this->storeMessage($conversation->id, 'user', e($message), 'html', metadata: $metadata);

        $result = $this->runtime->run(
            $agent,
            new AgentExecutionContext(
                message: $message,
                metadata: $metadata,
                conversationId: $conversation->id,
            ),
        );

        if (! $result->success) {
            return [
                'success' => false,
                'error' => $result->error ?? 'Agent execution failed.',
                'conversation_id' => $conversation->id,
            ];
        }

        $structured = $this->replies->build($result->output);
        $assistant = $this->storeMessage(
            $conversation->id,
            'assistant',
            $structured['html'],
            $structured['format'],
            blocks: $structured['blocks'],
        );

        $prefix = (string) config('agentic.widget.broadcast.channel_prefix', 'agentic-widget');
        $this->broadcast->publish($prefix.'.'.$conversation->id, 'message.created', [
            'message' => $this->serializeMessage($assistant, $structured['blocks']),
        ]);

        return [
            'success' => true,
            'conversation_id' => $conversation->id,
            'message' => $this->serializeMessage($assistant, $structured['blocks']),
            'usage' => [
                'tokens_in' => $assistant->tokens_in,
                'tokens_out' => $assistant->tokens_out,
                'tokens_total' => $assistant->tokens_total,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $blocks
     */
    private function storeMessage(
        string $conversationUuid,
        string $role,
        string $html,
        string $format,
        ?array $blocks = null,
        array $metadata = [],
    ): ConversationMessage {
        $conversation = Conversation::query()->where('uuid', $conversationUuid)->firstOrFail();

        $payload = $metadata;
        if ($blocks !== null) {
            $payload['blocks'] = $blocks;
        }

        return ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content_html' => $html,
            'format' => $format,
            'locale' => app()->getLocale(),
            'metadata' => $payload === [] ? null : $payload,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>|null  $blocks
     * @return array<string, mixed>
     */
    private function serializeMessage(ConversationMessage $message, ?array $blocks = null): array
    {
        $blocks ??= is_array($message->metadata['blocks'] ?? null) ? $message->metadata['blocks'] : null;

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
    }
}
