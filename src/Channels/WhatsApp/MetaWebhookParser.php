<?php

namespace Agentic\Channels\WhatsApp;

final class MetaWebhookParser
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{phone_number_id: string, from: string, text: string, message_id: string}>
     */
    public function inboundTexts(array $payload): array
    {
        $out = [];
        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
                foreach ($value['messages'] ?? [] as $message) {
                    if (! is_array($message) || ($message['type'] ?? '') !== 'text') {
                        continue;
                    }
                    $text = trim((string) ($message['text']['body'] ?? ''));
                    $from = trim((string) ($message['from'] ?? ''));
                    if ($text === '' || $from === '' || $phoneNumberId === '') {
                        continue;
                    }
                    $out[] = [
                        'phone_number_id' => $phoneNumberId,
                        'from' => $from,
                        'text' => $text,
                        'message_id' => (string) ($message['id'] ?? ''),
                    ];
                }
            }
        }

        return $out;
    }
}
