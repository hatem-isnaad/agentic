<?php

namespace Agentic\Http\Middleware;

use Agentic\Models\WidgetEmbedToken;
use Agentic\Widget\Embed\WidgetEmbedCredentialResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When embed.require_token is enabled, every widget API call must present a valid
 * embed token (wgt_…) or an allowed Sanctum session/token.
 */
final class AuthenticateWidgetEmbed
{
    public function __construct(
        private WidgetEmbedCredentialResolver $credentials,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('agentic.widget.embed.require_token', false)) {
            return $next($request);
        }

        $user = $request->user();
        $plain = $this->credentials->resolveBearer($request);
        $embed = $plain !== null ? $this->credentials->findToken($plain) : null;

        if ($embed !== null) {
            if ($embed->isExpired()) {
                return $this->deny('Embed token expired.');
            }

            $origin = $request->header('Origin') ?: $request->header('Referer');
            if (! $embed->allowsOrigin($this->normalizeOrigin($origin))) {
                return $this->deny('Origin not allowed for this embed token.');
            }

            $embed->forceFill(['last_used_at' => now()])->saveQuietly();
            $request->attributes->set('agentic_widget_embed', $embed);

            return $next($request);
        }

        if ($user !== null && (bool) config('agentic.widget.embed.sanctum_allowed', true)) {
            return $next($request);
        }

        return $this->deny('Valid embed token or authenticated user required.');
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

    private function deny(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }
}
