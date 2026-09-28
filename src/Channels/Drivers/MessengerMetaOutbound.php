<?php

namespace Agentic\Channels\Drivers;

use Agentic\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;

final class MessengerMetaOutbound implements ChannelOutbound
{
    public function sendText(ChannelAccount $account, string $to, string $text): bool
    {
        $token = (string) $account->credential('access_token', '');
        $pageId = (string) ($account->external_id ?: '');
        if ($token === '' || $pageId === '' || $to === '' || $text === '') {
            return false;
        }

        $version = (string) $account->configValue('graph_version', config('agentic.channels.messenger.graph_version', 'v21.0'));
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://graph.facebook.com/{$version}/{$pageId}/messages", [
                'recipient' => ['id' => $to],
                'messaging_type' => 'RESPONSE',
                'message' => ['text' => $text],
            ]);

        return $response->successful();
    }
}
