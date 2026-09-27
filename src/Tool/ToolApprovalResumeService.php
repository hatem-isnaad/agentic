<?php

namespace Agentic\Tool;

use Agentic\Models\ToolApproval;
use Agentic\Widget\WidgetChatService;

final class ToolApprovalResumeService
{
    public function __construct(
        private WidgetChatService $chat,
    ) {}

    /**
     * @param  array<string, mixed>  $executionResult
     * @return array<string, mixed>|null
     */
    public function maybeResume(ToolApproval $approval, array $executionResult): ?array
    {
        if (! config('agentic.tool_approval.auto_resume_after_execute', true)) {
            return null;
        }

        if (! is_string($approval->conversation_uuid) || $approval->conversation_uuid === '') {
            return null;
        }

        if ($approval->agent === '') {
            return null;
        }

        if (($executionResult['success'] ?? false) !== true) {
            return null;
        }

        return $this->chat->resumeAfterToolApproval($approval, $executionResult);
    }
}
