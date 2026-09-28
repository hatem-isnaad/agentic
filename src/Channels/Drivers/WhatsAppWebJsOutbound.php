<?php

namespace Agentic\Channels\Drivers;

use Agentic\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;

/** Sidecar (whatsapp-web.js, WAHA, Evolution). We POST text; the Node client talks to WhatsApp. */
final class WhatsAppWebJsOutbound implements ChannelOutbound
{
    public function sendText(ChannelAccount $account, string $to, string $text): bool
    {
        $url = rtrim((string) $account->configValue('sidecar_url', ''), '/');
        if ($url === '' || $to === '' || $text === '') {
            return false;
        }

        $secret = (string) $account->credential('sidecar_secret', '');
        $session = (string) ($account->external_id ?: $account->configValue('session', 'default'));
        $response = Http::withHeaders($secret !== '' ? ['X-Agentic-Channel-Secret' => $secret] : [])
            ->acceptJson()
            ->post($url.'/send', [
                'session' => $session,
                'to' => $to,
                'text' => $text,
            ]);

        return $response->successful();
    }
}
