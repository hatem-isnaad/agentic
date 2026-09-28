<?php

namespace Agentic\Channels\Drivers;

use Agentic\Models\ChannelAccount;

interface ChannelOutbound
{
    public function sendText(ChannelAccount $account, string $to, string $text): bool;
}
