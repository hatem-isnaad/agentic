<?php

namespace Agentic\Tool\Handlers;

use Agentic\Conversation\ConversationHandoffService;
use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;

final class HandoffCodeHandler implements CodeToolHandler
{
    public function __construct(private ConversationHandoffService $handoff) {}

    public function handle(ToolExecutionContext $context): ToolResult
    {
        $conversationId = $context->execution?->conversationId;
        if (! is_string($conversationId) || $conversationId === '') {
            $conversationId = is_string($context->metadata['conversation_id'] ?? null)
                ? $context->metadata['conversation_id']
                : '';
        }
        if ($conversationId === '') {
            return ToolResult::failure('No conversation to hand off.');
        }

        $reason = trim((string) ($context->arguments['reason'] ?? ''));
        $this->handoff->request($conversationId, $reason, 'agent');

        return ToolResult::success([
            'status' => ConversationHandoffService::Requested,
            'message' => 'A human teammate will take this chat. Tell the user you are connecting them.',
        ]);
    }
}
