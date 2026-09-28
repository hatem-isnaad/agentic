<?php

namespace Agentic\Channels;

use Agentic\Agent\AgentResolver;
use Agentic\Context\RuntimeContext;
use Agentic\Conversation\ConversationManager;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Models\ChannelAccount;
use Agentic\Models\Conversation as ConversationModel;
use Agentic\Models\ConversationMessage;
use Agentic\Reply\ChannelReplyPresenterFactory;
use Agentic\Widget\Reply\WidgetAssistantOutputSanitizer;
use Illuminate\Support\Str;

final class ChannelInboundService
{
    public function __construct(
        private AgentResolver $agents,
        private ChannelAgentRunner $runtime,
        private ConversationManager $conversations,
        private ChannelReplyPresenterFactory $presenters,
        private ChannelOutboundFactory $outbound,
    ) {}

    public function handleText(ChannelAccount $account, string $from, string $text): bool
    {
        $agentSlug = (string) ($account->agent_slug ?: '');
        if ($agentSlug === '' || $text === '') {
            return false;
        }

        try {
            $agent = $this->agents->resolve($agentSlug);
        } catch (AgentNotFoundException) {
            return false;
        }

        $userId = $account->channel.':'.$account->id.':'.$from;
        $conversation = $this->conversations->continueOrStart(
            agent: $agentSlug,
            userId: $userId,
            metadata: ['channel' => $account->channel, 'channel_account_id' => $account->id, 'from' => $from],
        );

        $this->store($conversation->id, 'user', e($text), 'text', $agentSlug, $userId, ['channel' => $account->channel, 'channel_account_id' => $account->id, 'from' => $from]);

        $result = $this->runtime->run($agent, new AgentExecutionContext(
            message: $text,
            metadata: ['channel' => $account->channel, 'channel_account_id' => $account->id],
            runtime: new RuntimeContext(['channel' => $account->channel]),
            conversationId: $conversation->id,
        ));

        if (! $result->success) {
            return false;
        }

        $sanitized = WidgetAssistantOutputSanitizer::cleanOutput($result->output);
        $presented = $this->presenters->forChannel($account->channel)->present($sanitized);
        $this->store($conversation->id, 'assistant', $presented->text !== '' ? $presented->text : $presented->html, $presented->format, $agentSlug, $userId);

        return $this->outbound->for($account)->sendText($account, $from, $presented->text !== '' ? $presented->text : trim(strip_tags($presented->html)));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function store(string $conversationUuid, string $role, string $body, string $format, string $agentSlug = '', ?string $userId = null, array $metadata = []): void
    {
        $conversation = ConversationModel::query()->firstOrCreate(
            ['uuid' => $conversationUuid],
            ['agent' => $agentSlug, 'user_id' => $userId, 'metadata' => $metadata],
        );

        ConversationMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content_html' => $body,
            'format' => $format,
            'locale' => app()->getLocale(),
        ]);
        $conversation->touch();
    }
}
