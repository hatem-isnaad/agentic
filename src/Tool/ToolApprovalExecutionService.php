<?php

namespace Agentic\Tool;

use Agentic\Conversation\HandoffOfferService;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;
use Agentic\Widget\Services\WidgetMessageService;

final class ToolApprovalExecutionService
{
    public function __construct(
        private ToolApprovalService $approvals,
        private ToolRegistry $tools,
        private ToolExecutor $executor,
        private WidgetBroadcastDriver $broadcast,
        private WidgetMessageService $messages,
        private HandoffOfferService $handoffOffers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $approvalUuid): array
    {
        $approval = $this->approvals->find($approvalUuid);

        if ($approval === null) {
            return ['success' => false, 'message' => 'Approval not found.'];
        }

        if ($approval->status !== 'approved') {
            return ['success' => false, 'message' => 'Approval is not approved.'];
        }

        if ($approval->tool === HandoffOfferService::Tool) {
            $this->handoffOffers->ensureRegistered();
        }

        $tool = $this->tools->resolve($approval->tool);

        $result = $this->executor->execute($tool, new ToolExecutionContext(
            arguments: is_array($approval->arguments) ? $approval->arguments : [],
            metadata: [
                'approval_id' => $approval->uuid,
                'conversation_id' => $approval->conversation_uuid,
                'execution_id' => $approval->execution_uuid,
            ],
        ));

        $isHandoff = $approval->tool === HandoffOfferService::Tool;
        if ($isHandoff && $result->success && is_string($approval->conversation_uuid) && $approval->conversation_uuid !== '') {
            $this->messages->announceHandoff($approval->conversation_uuid, false);
        }

        $okHtml = $isHandoff
            ? '<p>A person will take this chat shortly.</p>'
            : '<p>Approved. <code>'.e($approval->tool).'</code> ran.</p>';
        $okText = $isHandoff ? 'A person will take this chat shortly.' : 'Approved. '.$approval->tool.' ran.';

        $payload = [
            'success' => $result->success,
            'approval_id' => $approval->uuid,
            'tool' => $approval->tool,
            'result' => $result->data,
            'error' => $result->error,
            'handoff' => $isHandoff && $result->success,
            'resume' => [
                'success' => $result->success,
                'conversation_id' => $approval->conversation_uuid,
                'message' => [
                    'role' => 'assistant',
                    'html' => $result->success
                        ? $okHtml
                        : '<p>Approved, but the tool failed: '.e((string) $result->error).'</p>',
                    'text' => $result->success
                        ? $okText
                        : 'Approved, but the tool failed.',
                ],
            ],
        ];

        if (is_string($approval->conversation_uuid) && $approval->conversation_uuid !== '') {
            $prefix = (string) config('agentic.widget.broadcast.channel_prefix', 'agentic-widget');
            $this->broadcast->publish(
                $prefix.'.'.$approval->conversation_uuid,
                'tool.approval.executed',
                $payload,
            );
        }

        return $payload;
    }
}
