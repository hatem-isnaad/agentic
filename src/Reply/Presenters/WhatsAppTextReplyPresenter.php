<?php

namespace Agentic\Reply\Presenters;

use Agentic\Reply\Contracts\ChannelReplyPresenter;
use Agentic\Reply\PresentedReply;

/**
 * WhatsApp / Messenger: no HTML. Use each network's own text markers.
 */
final class WhatsAppTextReplyPresenter implements ChannelReplyPresenter
{
    public function present(array|string|null $payload): PresentedReply
    {
        $text = $this->sourceText($payload);
        $text = $this->toWhatsApp($text);

        return new PresentedReply(
            format: 'text',
            html: '',
            text: $text,
            blocks: [['type' => 'text', 'text' => $text]],
        );
    }

    /**
     * @param  array<string, mixed>|string|null  $payload
     */
    private function sourceText(array|string|null $payload): string
    {
        if (is_string($payload)) {
            return trim($payload);
        }

        if (! is_array($payload)) {
            return '';
        }

        if (isset($payload['text']) && is_string($payload['text'])) {
            return trim($payload['text']);
        }

        if (isset($payload['content']) && is_string($payload['content'])) {
            return trim($payload['content']);
        }

        return trim(strip_tags((string) json_encode($payload)));
    }

    private function toWhatsApp(string $text): string
    {
        $text = preg_replace('/^#{1,6}\s+/m', '', $text) ?? $text;

        $bolds = [];
        $text = preg_replace_callback('/\*\*(.+?)\*\*/s', function (array $matches) use (&$bolds): string {
            $bolds[] = $matches[1];

            return "\x01BOLD".(count($bolds) - 1)."\x01";
        }, $text) ?? $text;
        $text = preg_replace_callback('/__(.+?)__/s', function (array $matches) use (&$bolds): string {
            $bolds[] = $matches[1];

            return "\x01BOLD".(count($bolds) - 1)."\x01";
        }, $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '_$1_', $text) ?? $text;

        foreach ($bolds as $index => $inner) {
            $text = str_replace("\x01BOLD{$index}\x01", '*'.$inner.'*', $text);
        }

        return trim(strip_tags($text));
    }
}
