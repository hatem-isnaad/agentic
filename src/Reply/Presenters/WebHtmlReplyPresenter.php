<?php

namespace Agentic\Reply\Presenters;

use Agentic\Reply\Contracts\ChannelReplyPresenter;
use Agentic\Reply\PresentedReply;
use Agentic\Widget\Reply\StructuredReplyBuilder;

/**
 * Web chat / admin / widget: Markdown and blocks become themed HTML.
 */
final class WebHtmlReplyPresenter implements ChannelReplyPresenter
{
    public function __construct(
        private StructuredReplyBuilder $builder,
    ) {}

    public function present(array|string|null $payload): PresentedReply
    {
        $built = $this->builder->build($payload);

        return new PresentedReply(
            format: $built['format'],
            html: $built['html'],
            text: trim(strip_tags($built['html'])),
            blocks: $built['blocks'],
        );
    }
}
