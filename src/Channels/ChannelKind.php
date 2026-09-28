<?php

namespace Agentic\Channels;

enum ChannelKind: string
{
    case Widget = 'widget';
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
