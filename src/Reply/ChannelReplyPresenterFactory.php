<?php

namespace Agentic\Reply;

use Agentic\Reply\Contracts\ChannelReplyPresenter;
use Agentic\Reply\Presenters\WebHtmlReplyPresenter;
use Agentic\Reply\Presenters\WhatsAppTextReplyPresenter;
use Agentic\Widget\Reply\StructuredReplyBuilder;

final class ChannelReplyPresenterFactory
{
    public function __construct(
        private StructuredReplyBuilder $htmlBuilder,
    ) {}

    public function forChannel(string|ReplyChannel $channel): ChannelReplyPresenter
    {
        $channel = ReplyChannel::fromRuntime($channel);

        return match ($channel) {
            ReplyChannel::WhatsApp, ReplyChannel::Messenger => new WhatsAppTextReplyPresenter(),
            default => new WebHtmlReplyPresenter($this->htmlBuilder),
        };
    }
}
