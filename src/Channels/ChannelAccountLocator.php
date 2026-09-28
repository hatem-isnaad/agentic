<?php

namespace Agentic\Channels;

use Agentic\Models\ChannelAccount;

final class ChannelAccountLocator
{
    public function findWhatsAppMeta(string $phoneNumberId): ?ChannelAccount
    {
        if ($phoneNumberId === '') {
            return null;
        }

        return ChannelAccount::query()
            ->where('channel', ChannelKind::WhatsApp->value)
            ->where('driver', ChannelDriver::MetaCloud->value)
            ->where('external_id', $phoneNumberId)
            ->where('status', 'active')
            ->first();
    }

    public function findWhatsAppWebJs(string $session): ?ChannelAccount
    {
        if ($session === '') {
            return null;
        }

        return ChannelAccount::query()
            ->where('channel', ChannelKind::WhatsApp->value)
            ->where('driver', ChannelDriver::WebJs->value)
            ->where('status', 'active')
            ->get()
            ->first(function (ChannelAccount $account) use ($session): bool {
                return $account->external_id === $session
                    || (string) $account->configValue('session', '') === $session;
            });
    }

    public function matchesMetaVerifyToken(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $fromConfig = (string) config('agentic.channels.whatsapp.verify_token', '');
        if ($fromConfig !== '' && hash_equals($fromConfig, $token)) {
            return true;
        }

        return ChannelAccount::query()
            ->where('channel', ChannelKind::WhatsApp->value)
            ->where('driver', ChannelDriver::MetaCloud->value)
            ->where('status', 'active')
            ->get()
            ->contains(fn (ChannelAccount $account): bool => hash_equals((string) $account->credential('verify_token', ''), $token));
    }

    public function findMessengerMeta(string $pageId): ?ChannelAccount
    {
        if ($pageId === '') {
            return null;
        }

        return ChannelAccount::query()
            ->where('channel', ChannelKind::Messenger->value)
            ->where('driver', ChannelDriver::MetaCloud->value)
            ->where('external_id', $pageId)
            ->where('status', 'active')
            ->first();
    }

    public function matchesMessengerVerifyToken(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $fromConfig = (string) config('agentic.channels.messenger.verify_token', '');
        if ($fromConfig !== '' && hash_equals($fromConfig, $token)) {
            return true;
        }

        return ChannelAccount::query()
            ->where('channel', ChannelKind::Messenger->value)
            ->where('driver', ChannelDriver::MetaCloud->value)
            ->where('status', 'active')
            ->get()
            ->contains(fn (ChannelAccount $account): bool => hash_equals((string) $account->credential('verify_token', ''), $token));
    }

    public function matchesWebJsSecret(ChannelAccount $account, string $secret): bool
    {
        $expected = (string) $account->credential('sidecar_secret', '');

        return $expected !== '' && $secret !== '' && hash_equals($expected, $secret);
    }
}
