<?php

namespace Agentic\Tool;

use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Widget\Broadcast\WidgetBroadcastDriver;

final class ToolApprovalExecutionService
{
    public function __construct(
        private ToolApprovalService $approvals,
        private ToolRegistry $tools,
        private ToolExecutor $executor,
        private WidgetBroadcastDriver $broadcast,
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

        $tool = $this->tools->resolve($approval->tool);

        $result = $this->executor->execute($tool, new ToolExecutionContext(
            arguments: is_array($approval->arguments) ? $approval->arguments : [],
            metadata: [
                'approval_id' => $approval->uuid,
                'conversation_id' => $approval->conversation_uuid,
                'execution_id' => $approval->execution_uuid,
            ],
        ));

        $payload = [
            'success' => $result->success,
            'approval_id' => $approval->uuid,
            'tool' => $approval->tool,
            'result' => $result->data,
            'error' => $result->error,
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
