<?php

namespace Agentic\Http\Middleware;

use Agentic\Http\Support\AuthRequirement;
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
            if (
                $request->user() === null
                && AuthRequirement::enabled(config('agentic.auth.protect.admin_api'))
            ) {
                abort(401, __('agentic::errors.admin_sign_in_required'));
            }

            if (! Gate::allows($gate)) {
                abort(403, __('agentic::errors.admin_access_denied'));
            }

            return $next($request);
        }

        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        abort(403, __('agentic::errors.admin_gate_required'));
    }
}
