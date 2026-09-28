<?php

namespace Agentic\Channels\Messenger;

final class MessengerWebhookParser
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{page_id: string, from: string, text: string, message_id: string}>
     */
    public function inboundTexts(array $payload): array
    {
        $out = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $pageId = (string) ($entry['id'] ?? '');

            foreach ($entry['messaging'] ?? [] as $event) {
                if (! is_array($event)) {
                    continue;
                }

                $text = trim((string) ($event['message']['text'] ?? ''));
                $from = trim((string) ($event['sender']['id'] ?? ''));
                $recipient = trim((string) ($event['recipient']['id'] ?? $pageId));

                if ($text === '' || $from === '' || $recipient === '') {
                    continue;
                }

                if (($event['message']['is_echo'] ?? false) === true) {
                    continue;
                }

                $out[] = [
                    'page_id' => $recipient,
                    'from' => $from,
                    'text' => $text,
                    'message_id' => (string) ($event['message']['mid'] ?? ''),
                ];
            }
        }

        return $out;
    }
}
