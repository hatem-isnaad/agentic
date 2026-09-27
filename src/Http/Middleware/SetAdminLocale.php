<?php

namespace Agentic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('locale')
            ?? $request->header('X-Agentic-Locale')
            ?? config('agentic.admin.default_locale', 'en');

        if (is_string($locale) && in_array($locale, config('agentic.admin.locales', ['en', 'ar']), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
