<?php

namespace Agentic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional admin authorization (Telescope-style). When agentic.admin.authorization.gate
 * is set, the named gate must pass. When null, all requests are allowed (host should
 * add auth middleware separately for production). Gate callbacks should accept
 * ?$user = null so they run for guests when the admin API is not behind auth yet.
 */
final class AuthorizeAgenticAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $gate = config('agentic.admin.authorization.gate');

        if (! is_string($gate) || $gate === '') {
            return $next($request);
        }

        Gate::authorize($gate);

        return $next($request);
    }
}
