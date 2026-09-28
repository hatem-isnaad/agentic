<?php

namespace Agentic\Widget\Services;

use Agentic\Agent\AgentDefinition;
use Agentic\Agent\AgentResolver;
use Agentic\Context\RuntimeContext;
use Agentic\Conversation\ConversationHandoffService;
use Agentic\Conversation\ConversationManager;
use Agentic\Conversation\HandoffOfferService;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Jobs\ProcessWidgetMessageJob;
use Agentic\Models\Conversation;
use Agentic\Models\ConversationMessage;
use Agentic\Reply\ChannelReplyPresenterFactory;
use Agentic\Runtime\AgentRuntime;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Agentic\Widget\DTO\WidgetMessageData;
use Agentic\Widget\Reply\WidgetAssistantOutputSanitizer;
use Agentic\Widget\Support\WidgetMessageBatchSettings;
use Agentic\Widget\Support\WidgetReplyDelivery;
use Illuminate\Support\Str;

final class WidgetMessageService
{
    public function __construct(
        private AgentResolver $agents,
        private AgentRuntime $runtime,
        private ConversationManager $conversations,
        private ChannelReplyPresenterFactory $presenters,
        private WidgetBroadcastDriver $broadcast,
        private ConversationHandoffService $handoff,
        private WidgetAttachmentService $attachments,
        private HandoffOfferService $offers,
        private WidgetMessageHtmlPresenter $messageHtml,
        private WidgetMessageBatchCoordinator $messageBatch,
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
            : $this->conversations->start(agent: $data->agent, userId: $data->identity->conversationUserId(), metadata: $data->metadata);

        if ($data->files !== [] && ! $this->handoff->isStaffChatId($conversation->id)) {
            return [
                'success' => false,
                'error' => 'File uploads are only available when a team member is actively helping in this chat.',
                'conversation_id' => $conversation->id,
            ];
        }

        $files = $data->files !== [] ? $this->attachments->store($data->files, $conversation->id) : [];
        $html = e($data->message);
        $this->storeMessage($conversation->id, 'user', $html, 'html', metadata: array_merge(
            $data->metadata,
            $files !== [] ? ['attachments' => $files] : [],
        ));

        if ($this->handoff->isHuman($conversation)) {
            return [
                'conversation_id' => $conversation->id,
                'pending' => true,
                'handoff' => true,
            ];
        }

        if ($this->offers->userConfirmed($conversation->id, $data->message)) {
            $this->offers->closePending($conversation->id);

            return $this->requestHandoff($conversation->id, 'user confirmed');
        }

        if (WidgetReplyDelivery::isAsync()) {
            if (WidgetMessageBatchSettings::enabled() && $files === []) {
                $this->messageBatch->schedule($data->agent, $conversation->id, $data->metadata);

                return [
                    'conversation_id' => $conversation->id,
                    'pending' => true,
                    'batched' => true,
                ];
            }

            $this->publishTyping($conversation->id, true);
            ProcessWidgetMessageJob::dispatch($data->agent, $conversation->id, $data->message, $data->metadata, $files);

            return ['conversation_id' => $conversation->id, 'pending' => true];
        }

        return $this->runAgentTurn($data->agent, $conversation->id, $data->message, $data->metadata, $agent, $files);
    }

    /**
     * @return array<string, mixed>
     */
    public function requestHandoff(string $conversationId, string $reason = ''): array
    {
        $this->handoff->request($conversationId, $reason, 'widget');
        $this->announceHandoff($conversationId);

        return [
            'conversation_id' => $conversationId,
            'handoff' => true,
        ];
    }

    public function announceHandoff(string $conversationId, bool $broadcast = true): void
    {
        $exists = ConversationMessage::query()
            ->whereHas('conversation', fn ($query) => $query->where('uuid', $conversationId))
            ->where('metadata->source', 'handoff')
            ->exists();
        if ($exists) {
            return;
        }

        $assistant = $this->storeMessage(
            $conversationId,
            'assistant',
            e('A person will take this chat shortly.'),
            'html',
            metadata: ['source' => 'handoff'],
        );
        if ($broadcast) {
            $this->publishAssistantMessage($conversationId, $assistant, null);
        }
    }

    public function stopQueuedAgentTurn(string $conversationId): void
    {
        $this->publishTyping($conversationId, false);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function runAgentTurn(string $agentSlug, string $conversationId, string $message, array $metadata = [], ?AgentDefinition $agent = null, array $attachments = []): array
    {
        $conversation = $this->conversations->continue($conversationId);
        if ($this->handoff->isHuman($conversation)) {
            $this->publishTyping($conversationId, false);

            return [
                'conversation_id' => $conversationId,
                'pending' => true,
                'handoff' => true,
            ];
        }

        $this->publishTyping($conversationId, true);

        try {
            try {
                $agent ??= $this->agents->resolve($agentSlug);
            } catch (AgentNotFoundException) {
                $this->publishFailure($conversationId, 'Agent not found.');

                return ['success' => false, 'error' => 'Agent not found.'];
            }

            $result = $this->runtime->run($agent, $this->executionContextForWidgetTurn($conversationId, $message, $metadata, $attachments));

            if (! $result->success) {
                $error = $result->error ?? 'Agent execution failed.';
                $this->publishFailure($conversationId, $error);
                $this->appendHandoffOffer(
                    $conversationId,
                    $agentSlug,
                    is_array($result->output) ? (string) ($result->output['execution_id'] ?? '') : null,
                    [],
                    false,
                );

                return [
                    'success' => false,
                    'error' => $error,
                    'conversation_id' => $conversationId,
                ];
            }

            $runOutput = is_array($result->output) ? $result->output : [];
            $pending = is_array($runOutput['pending_approvals'] ?? null) ? $runOutput['pending_approvals'] : [];
            $output = WidgetAssistantOutputSanitizer::cleanOutput($result->output);
            if (is_array($output)) {
                $offerBlocks = $this->offers->maybeOfferBlocks(
                    $conversationId,
                    $agentSlug,
                    isset($runOutput['execution_id']) ? (string) $runOutput['execution_id'] : null,
                    $pending,
                    true,
                );
                if ($offerBlocks !== null) {
                    $output['format'] = 'blocks';
                    $output['blocks'] = array_merge(
                        is_array($output['blocks'] ?? null) ? $output['blocks'] : [],
                        $offerBlocks,
                    );
                }
            }
            $presented = $this->presenters->forChannel('widget')->present($output);
            $assistant = $this->storeMessage($conversationId, 'assistant', $presented->html, $presented->format, blocks: $presented->blocks);

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
     * @param  list<array<string, mixed>>  $attachments
     */
    private function executionContextForWidgetTurn(
        string $conversationId,
        string $message,
        array $metadata,
        array $attachments = [],
    ): AgentExecutionContext {
        $runtime = new RuntimeContext(['channel' => 'widget']);
        if (filter_var(config('agentic.widget.stream', false), FILTER_VALIDATE_BOOL)) {
            $runtime = $runtime->with('on_text_delta', function (string $delta) use ($conversationId): void {
                if ($delta === '') {
                    return;
                }
                $this->broadcast->publish(
                    WidgetReplyDelivery::conversationChannel($conversationId),
                    'message.delta',
                    ['delta' => $delta],
                );
            });
        }

        return new AgentExecutionContext(
            message: $message,
            metadata: array_merge($metadata, ['channel' => 'widget']),
            runtime: $runtime,
            conversationId: $conversationId,
            attachments: $attachments,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $pending
     */
    private function appendHandoffOffer(
        string $conversationId,
        string $agentSlug,
        ?string $executionId,
        array $pending,
        bool $succeeded,
    ): void {
        $blocks = $this->offers->maybeOfferBlocks($conversationId, $agentSlug, $executionId, $pending, $succeeded);
        if ($blocks === null) {
            return;
        }

        $presented = $this->presenters->forChannel('widget')->present([
            'format' => 'blocks',
            'text' => 'I could not finish this. Want a person to take over?',
            'blocks' => $blocks,
        ]);
        $assistant = $this->storeMessage($conversationId, 'assistant', $presented->html, $presented->format, blocks: $presented->blocks);
        $this->publishAssistantMessage($conversationId, $assistant, $presented->blocks);
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
        $message->setRelation('conversation', $conversation);

        return $message;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function presentMessageAttachments(ConversationMessage $message): array
    {
        $files = is_array($message->metadata['attachments'] ?? null) ? $message->metadata['attachments'] : [];
        $conversationId = (string) ($message->conversation?->uuid ?? '');

        return $conversationId !== '' ? $this->attachments->present($files, $conversationId) : [];
    }

    private function presentMessageHtml(ConversationMessage $message): string
    {
        return $this->messageHtml->render($message);
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
            'html' => $this->presentMessageHtml($message),
            'format' => $message->format,
            'blocks' => $blocks,
            'attachments' => $this->presentMessageAttachments($message),
            'tokens_in' => $message->tokens_in,
            'tokens_out' => $message->tokens_out,
            'tokens_total' => $message->tokens_total,
            'locale' => $message->locale,
            'created_at' => optional($message->created_at)?->toISOString(),
            'source' => is_string($message->metadata['source'] ?? null) ? $message->metadata['source'] : null,
        ];
    }
}
