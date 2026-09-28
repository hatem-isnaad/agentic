<?php

namespace Agentic\Channels;

use Agentic\Channels\Drivers\ChannelOutbound;
use Agentic\Channels\Drivers\MessengerMetaOutbound;
use Agentic\Channels\Drivers\WhatsAppMetaOutbound;
use Agentic\Channels\Drivers\WhatsAppWebJsOutbound;
use Agentic\Models\ChannelAccount;
use InvalidArgumentException;

final class ChannelOutboundFactory
{
    public function for(ChannelAccount $account): ChannelOutbound
    {
        if ($account->channel === ChannelKind::Messenger->value) {
            return new MessengerMetaOutbound;
        }

        return match ($account->driver) {
            ChannelDriver::MetaCloud->value => new WhatsAppMetaOutbound,
            ChannelDriver::WebJs->value => new WhatsAppWebJsOutbound,
            default => throw new InvalidArgumentException('Channel driver ['.$account->driver.'] cannot send outbound messages.'),
        };
    }
}
