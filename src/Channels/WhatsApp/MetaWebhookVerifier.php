<?php

namespace Agentic\Channels\WhatsApp;

use Agentic\Models\ChannelAccount;

final class MetaWebhookVerifier
{
    public function validSignature(string $rawBody, string $header, ?ChannelAccount $account = null): bool
    {
        if ($header === '' || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $secret = (string) ($account?->credential('app_secret', '')
            ?: config('agentic.channels.whatsapp.app_secret', '')
            ?: config('agentic.channels.messenger.app_secret', ''));
        if ($secret === '') {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $rawBody, $secret), $header);
    }
}
