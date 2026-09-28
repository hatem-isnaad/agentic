<?php

namespace Agentic\Widget\Services;

use Agentic\Models\ConversationMessage;
use Agentic\Widget\Reply\HtmlReplyRenderer;
use Agentic\Widget\Reply\WidgetApprovalCard;

final class WidgetMessageHtmlPresenter
{
    public function __construct(
        private HtmlReplyRenderer $html,
        private WidgetAttachmentService $attachments,
    ) {}

    public function render(ConversationMessage $message, bool $adminUrls = false): string
    {
        $blocks = is_array($message->metadata['blocks'] ?? null) ? $message->metadata['blocks'] : null;
        $base = ($blocks !== null && $blocks !== [])
            ? $this->html->renderBlocks(WidgetApprovalCard::normalizeBlocks($blocks))
            : (string) $message->content_html;

        $files = is_array($message->metadata['attachments'] ?? null) ? $message->metadata['attachments'] : [];
        $conversationId = (string) ($message->conversation?->uuid ?? '');

        return $this->attachments->presentHtml($base, $files, $conversationId, $adminUrls);
    }
}
