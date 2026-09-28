<?php

namespace Agentic\Http\Middleware;

use Agentic\Models\WidgetEmbedToken;
use Agentic\Widget\Embed\WidgetEmbedRequestValidator;
use Agentic\Widget\Support\WidgetFileSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates embed token (DB), allowed host/origin, agent allowlist, and guest vs user identity on every widget request.
 */
final class ValidateWidgetEmbed
{
    public function __construct(
        private WidgetEmbedRequestValidator $validator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (WidgetFileSignature::isValid($request)) {
            return $next($request);
        }

        $tokenResult = $this->validator->validateTokenAndOrigin($request);
        if ($tokenResult['ok'] === false) {
            return $this->json($tokenResult['message'], $tokenResult['status']);
        }

        /** @var WidgetEmbedToken|null $embed */
        $embed = $tokenResult['token'];
        if ($embed instanceof WidgetEmbedToken) {
            $request->attributes->set('agentic_widget_embed', $embed);
        } elseif (! filter_var(config('agentic.widget.embed.require_token', true), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $identity = $this->validator->validateIdentity($request, $embed);
        if ($identity['ok'] === false) {
            return $this->json($identity['message'], 403);
        }

        $agent = $this->validator->resolveAgentSlug($request);
        if ($embed instanceof WidgetEmbedToken && $agent !== null && ! $embed->allowsAgent($agent)) {
            return $this->json('Agent not allowed for this embed token.', 403);
        }

        return $next($request);
    }

    private function json(string $message, int $status): Response
    {
        return response()->json(['message' => $message], $status);
    }

}
