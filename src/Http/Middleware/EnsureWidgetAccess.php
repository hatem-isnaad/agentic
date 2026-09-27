<?php

namespace Agentic\Http\Middleware;

use Agentic\Models\WidgetEmbedToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWidgetAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = (string) config('agentic.widget.auth.mode', 'both');
        $guestAllowed = (bool) config('agentic.widget.auth.allow_guest', true);
        $authAllowed = (bool) config('agentic.widget.auth.allow_authenticated', true);

        $embed = $request->attributes->get('agentic_widget_embed');
        if ($embed instanceof WidgetEmbedToken) {
            $guestAllowed = $embed->guest_allowed;
            if (! $embed->sanctum_allowed) {
                $authAllowed = false;
            }
        }

        $authenticated = $request->user() !== null;
        $hasGuest = is_string($request->header('X-Agentic-Guest-Id')) && $request->header('X-Agentic-Guest-Id') !== '';

        $allowed = match ($mode) {
            'guest' => ! $authenticated && $guestAllowed && $hasGuest,
            'auth' => $authenticated && $authAllowed,
            default => ($authenticated && $authAllowed) || ((! $authenticated) && $guestAllowed && $hasGuest),
        };

        if (! $allowed) {
            return response()->json(['message' => 'Widget access denied for this identity.'], 403);
        }

        return $next($request);
    }
}
