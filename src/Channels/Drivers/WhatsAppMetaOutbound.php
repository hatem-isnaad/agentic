<?php

namespace Agentic\Channels\Drivers;

use Agentic\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;

final class WhatsAppMetaOutbound implements ChannelOutbound
{
    public function sendText(ChannelAccount $account, string $to, string $text): bool
    {
        $token = (string) $account->credential('access_token', '');
        $phoneNumberId = (string) ($account->external_id ?: '');
        if ($token === '' || $phoneNumberId === '' || $to === '' || $text === '') {
            return false;
        }

        $version = (string) $account->configValue('graph_version', config('agentic.channels.whatsapp.graph_version', 'v21.0'));
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $text, 'preview_url' => false],
            ]);

        return $response->successful();
    }
}
