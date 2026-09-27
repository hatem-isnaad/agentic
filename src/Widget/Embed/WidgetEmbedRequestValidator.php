<?php

namespace Agentic\Widget\Embed;

use Agentic\Models\WidgetEmbedToken;
use Illuminate\Http\Request;

/**
 * Single place for embed token + host + identity rules (config, messages, every widget route).
 */
final class WidgetEmbedRequestValidator
{
    public function __construct(
        private WidgetEmbedCredentialResolver $credentials,
    ) {}

    /**
     * @return array{ok: true, token: WidgetEmbedToken|null}|array{ok: false, message: string, status: int}
     */
    public function validateTokenAndOrigin(Request $request): array
    {
        if (! filter_var(config('agentic.widget.embed.require_token', true), FILTER_VALIDATE_BOOLEAN)) {
            return ['ok' => true, 'token' => null];
        }

        $plain = $this->credentials->resolveBearer($request);
        if ($plain === null || $plain === '') {
            return ['ok' => false, 'message' => 'Embed token required.', 'status' => 401];
        }

        $embed = $this->credentials->findToken($plain);
        if ($embed === null) {
            return ['ok' => false, 'message' => 'Invalid embed token.', 'status' => 401];
        }

        if (! $embed->enabled) {
            return ['ok' => false, 'message' => 'Embed token disabled.', 'status' => 401];
        }

        if ($embed->isExpired()) {
            return ['ok' => false, 'message' => 'Embed token expired.', 'status' => 401];
        }

        $origin = $this->normalizeOrigin($request->header('Origin') ?: $request->header('Referer'));
        if (! $embed->allowsOrigin($origin)) {
            return ['ok' => false, 'message' => 'Origin not allowed for this embed token.', 'status' => 401];
        }

        $embed->forceFill(['last_used_at' => now()])->saveQuietly();

        return ['ok' => true, 'token' => $embed];
    }

    /**
     * @return array{ok: true}|array{ok: false, message: string}
     */
    public function validateIdentity(Request $request, ?WidgetEmbedToken $embed): array
    {
        if ($embed === null) {
            return $this->validateIdentityFromGlobalConfig($request);
        }

        $authenticated = $request->user() !== null;
        $guestId = $request->header('X-Agentic-Guest-Id');
        $hasGuest = is_string($guestId) && $guestId !== '';
        $userHeader = $request->header('X-Agentic-User-Id');
        $hasUserHeader = is_string($userHeader) && $userHeader !== '';

        if ($authenticated && $embed->sanctum_allowed) {
            return ['ok' => true];
        }

        if ($embed->guest_allowed && $hasGuest) {
            return ['ok' => true];
        }

        if (! $embed->guest_allowed && ($hasUserHeader || $authenticated)) {
            return ['ok' => true];
        }

        if ($embed->guest_allowed && ! $hasGuest && ! $authenticated) {
            return ['ok' => false, 'message' => 'X-Agentic-Guest-Id required for this embed token.'];
        }

        return ['ok' => false, 'message' => 'Widget access denied: signed-in user id required for this embed token.'];
    }

    public function resolveAgentSlug(Request $request): ?string
    {
        $agent = $request->query('agent')
            ?? $request->input('agent')
            ?? $request->route('agent');

        return is_string($agent) && $agent !== '' ? $agent : null;
    }

    public function resolveGuestId(Request $request): ?string
    {
        $guestId = $request->header('X-Agentic-Guest-Id');

        return is_string($guestId) && $guestId !== '' ? $guestId : null;
    }

    /** Conversation user_id column value for a signed-in site user (not guest). */
    public function resolveUserIdentity(Request $request): ?string
    {
        $authId = $request->user()?->getAuthIdentifier();
        if ($authId !== null) {
            return 'user:'.(string) $authId;
        }

        $header = $request->header('X-Agentic-User-Id');
        if (is_string($header) && $header !== '') {
            return 'user:'.$header;
        }

        return null;
    }

    private function validateIdentityFromGlobalConfig(Request $request): array
    {
        $mode = (string) config('agentic.widget.auth.mode', 'both');
        $guestAllowed = filter_var(config('agentic.widget.auth.allow_guest', true), FILTER_VALIDATE_BOOLEAN);
        $authAllowed = filter_var(config('agentic.widget.auth.allow_authenticated', true), FILTER_VALIDATE_BOOLEAN);
        $authenticated = $request->user() !== null;
        $hasGuest = is_string($request->header('X-Agentic-Guest-Id')) && $request->header('X-Agentic-Guest-Id') !== '';

        $allowed = match ($mode) {
            'guest' => ! $authenticated && $guestAllowed && $hasGuest,
            'auth' => $authenticated && $authAllowed,
            default => ($authenticated && $authAllowed) || ((! $authenticated) && $guestAllowed && $hasGuest),
        };

        if (! $allowed) {
            return ['ok' => false, 'message' => 'Widget access denied for this identity.'];
        }

        return ['ok' => true];
    }

    private function normalizeOrigin(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = parse_url($value);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $value;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
    }
}
