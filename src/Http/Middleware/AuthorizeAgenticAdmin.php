<?php

namespace Agentic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorization after authentication. Sanctum is attached first when
 * AGENTIC_ADMIN_REQUIRE_AUTH is true (package default). A configured gate
 * always runs. With no gate, local and testing stay open; production is 403.
 */
final class AuthorizeAgenticAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $gate = config('agentic.admin.authorization.gate');

        if (is_string($gate) && $gate !== '') {
            Gate::authorize($gate);

            return $next($request);
        }

        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        abort(403, 'Define AGENTIC_ADMIN_GATE and Gate::define() before exposing Agentic admin.');
    }
}
