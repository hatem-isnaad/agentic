<?php

namespace Agentic\Reply;

enum ReplyChannel: string
{
    case Widget = 'widget';
    case Admin = 'admin';
    case Web = 'web';
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';

    public static function fromRuntime(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom(is_string($value) ? $value : '') ?? self::Web;
    }

    public function usesHtml(): bool
    {
        return match ($this) {
            self::Widget, self::Admin, self::Web => true,
            self::WhatsApp, self::Messenger => false,
        };
    }
}
