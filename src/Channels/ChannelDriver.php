<?php

namespace Agentic\Channels;

enum ChannelDriver: string
{
    case Embed = 'embed';
    case MetaCloud = 'meta_cloud';
    case WebJs = 'webjs';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function allowedFor(ChannelKind $channel): array
    {
        return match ($channel) {
            ChannelKind::Widget => [self::Embed],
            ChannelKind::WhatsApp => [self::MetaCloud, self::WebJs],
            ChannelKind::Messenger => [self::MetaCloud],
        };
    }
}
