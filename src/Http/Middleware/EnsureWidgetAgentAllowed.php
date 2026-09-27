<?php

namespace Agentic\Http\Middleware;

use Agentic\Models\WidgetEmbedToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWidgetAgentAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var WidgetEmbedToken|null $embed */
        $embed = $request->attributes->get('agentic_widget_embed');

        if ($embed === null) {
            return $next($request);
        }

        $agent = $request->query('agent')
            ?? $request->input('agent')
            ?? $request->route('agent');

        if (! is_string($agent) || $agent === '') {
            return $next($request);
        }

        if (! $embed->allowsAgent($agent)) {
            return response()->json(['message' => 'Agent not allowed for this embed token.'], 403);
        }

        return $next($request);
    }
}
