<?php

namespace Agentic\Reply\Contracts;

use Agentic\Reply\PresentedReply;

interface ChannelReplyPresenter
{
    /**
     * @param  array<string, mixed>|string|null  $payload
     */
    public function present(array|string|null $payload): PresentedReply;
}
