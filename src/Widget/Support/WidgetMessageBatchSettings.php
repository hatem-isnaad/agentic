<?php

namespace Agentic\Widget\Support;

final class WidgetMessageBatchSettings
{
    public static function enabled(): bool
    {
        return self::windowMs() > 0 && WidgetReplyDelivery::isAsync();
    }

    public static function windowMs(): int
    {
        return max(0, (int) config('agentic.widget.message_batch.window_ms', 0));
    }

    public static function maxMs(): int
    {
        $max = max(0, (int) config('agentic.widget.message_batch.max_ms', 10_000));
        $window = self::windowMs();

        if ($window > 0 && $max > 0 && $max < $window) {
            return $window;
        }

        return $max;
    }

    /**
     * @return array{window_ms: int, max_ms: int}
     */
    public static function clientPayload(): array
    {
        return [
            'window_ms' => self::enabled() ? self::windowMs() : 0,
            'max_ms' => self::enabled() ? self::maxMs() : 0,
        ];
    }
}
