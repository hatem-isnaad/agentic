<?php

namespace Agentic\Widget\Services;

use Agentic\Agent\AgentDefinition;
use Agentic\Agent\AgentResolver;
use Agentic\Conversation\ConversationManager;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Jobs\ProcessWidgetMessageJob;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Runtime\AgentRuntime;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Agentic\Reply\ChannelReplyPresenterFactory;
use Agentic\Widget\DTO\WidgetMessageData;
use Agentic\Widget\Reply\WidgetAssistantOutputSanitizer;
use Agentic\Widget\Support\WidgetReplyDelivery;
use Agentic\Context\RuntimeContext;
use Illuminate\Support\Str;

final class WidgetMessageService
{
    public function __construct(
        private AgentResolver $agents,
        private AgentRuntime $runtime,
        private ConversationManager $conversations,
        private ChannelReplyPresenterFactory $presenters,
        private WidgetBroadcastDriver $broadcast,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function send(WidgetMessageData $data): array
    {
        try {
            $agent = $this->agents->resolve($data->agent);
        } catch (AgentNotFoundException) {
            return ['success' => false, 'error' => 'Agent not found.'];
        }

        $conversation = $data->conversationId
            ? $this->conversations->continue($data->conversationId)
            : $this->conversations->start(
                agent: $data->agent,
                userId: $data->identity->conversationUserId(),
                tenantId: $data->identity->tenantId,
                metadata: $data->metadata,
            );

        $this->storeMessage($conversation->id, 'user', e($data->message), 'html', metadata: $data->metadata);

        if (WidgetReplyDelivery::isAsync()) {
            $this->publishTyping($conversation->id, true);
            ProcessWidgetMessageJob::dispatch($data->agent, $conversation->id, $data->message, $data->metadata);

            return ['conversation_id' => $conversation->id, 'pending' => true];
        }

        return $this->runAgentTurn($data->agent, $conversation->id, $data->message, $data->metadata, $agent);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function runAgentTurn(
        string $agentSlug,
        string $conversationId,
        string $message,
        array $metadata = [],
        ?AgentDefinition $agent = null,
    ): array {
        $this->publishTyping($conversationId, true);

        try {
            try {
                $agent ??= $this->agents->resolve($agentSlug);
            } catch (AgentNotFoundException) {
                $this->publishFailure($conversationId, 'Agent not found.');

                return ['success' => false, 'error' => 'Agent not found.'];
            }

            $result = $this->runtime->run(
                $agent,
                $this->executionContextForWidgetTurn($conversationId, $message, $metadata),
            );

            if (! $result->success) {
                $error = $result->error ?? 'Agent execution failed.';
                $this->publishFailure($conversationId, $error);

                return [
                    'success' => false,
                    'error' => $error,
                    'conversation_id' => $conversationId,
                ];
            }

            $presented = $this->presenters->forChannel('widget')->present(
                WidgetAssistantOutputSanitizer::cleanOutput($result->output),
            );
            $assistant = $this->storeMessage(
                $conversationId,
                'assistant',
                $presented->html,
                $presented->format,
                blocks: $presented->blocks,
            );

            $runOutput = is_array($result->output) ? $result->output : [];
            $executionId = isset($runOutput['execution_id']) ? (string) $runOutput['execution_id'] : null;
            $usage = is_array($runOutput['usage'] ?? null) ? $runOutput['usage'] : [];

            $this->publishAssistantMessage(
                $conversationId,
                $assistant,
                $presented->blocks,
                $executionId,
                $usage,
            );

            return [
                'success' => true,
                'conversation_id' => $conversationId,
                'text' => $presented->text !== '' ? $presented->text : trim(strip_tags($presented->html)),
                'message' => $this->serializeMessage($assistant, $presented->blocks),
                'result' => [
                    'execution_id' => $executionId,
                    'usage' => $usage !== [] ? $usage : [
                        'tokens_in' => $assistant->tokens_in,
                        'tokens_out' => $assistant->tokens_out,
                        'tokens_total' => $assistant->tokens_total,
                    ],
                ],
            ];
        } finally {
            $this->publishTyping($conversationId, false);
        }
    }

    /**
     * @param  list<array<string, mixed>>|null  $blocks
     */
    private function publishAssistantMessage(
        string $conversationId,
        ConversationMessage $assistant,
        ?array $blocks,
        ?string $executionId = null,
        array $usage = [],
    ): void {
        $payload = [
            'message' => $this->serializeMessage($assistant, $blocks),
        ];

        if ($executionId !== null || $usage !== []) {
            $payload['result'] = [
                'execution_id' => $executionId,
                'usage' => $usage,
            ];
        }

        $this->broadcast->publish(
            WidgetReplyDelivery::conversationChannel($conversationId),
            'message.created',
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function executionContextForWidgetTurn(
        string $conversationId,
        string $message,
        array $metadata,
    ): AgentExecutionContext {
        return new AgentExecutionContext(
            message: $message,
            metadata: array_merge($metadata, ['channel' => 'widget']),
            runtime: new RuntimeContext(['channel' => 'widget']),
            conversationId: $conversationId,
        );
    }

    private function publishFailure(string $conversationId, string $error): void
    {
        $this->broadcast->publish(
            WidgetReplyDelivery::conversationChannel($conversationId),
            'message.failed',
            ['error' => $error],
        );
    }

    private function publishTyping(string $conversationId, bool $active): void
    {
        $this->broadcast->publish(
            WidgetReplyDelivery::conversationChannel($conversationId),
            'assistant.typing',
            ['active' => $active],
        );
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

        $message = ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content_html' => $html,
            'format' => $format,
            'locale' => app()->getLocale(),
            'metadata' => $payload === [] ? null : $payload,
        ]);

        $conversation->touch();

        return $message;
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
            'cursor' => $message->id,
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
