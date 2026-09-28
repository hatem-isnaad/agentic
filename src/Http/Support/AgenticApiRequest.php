<?php

namespace Agentic\Http\Support;

use Illuminate\Http\Request;

final class AgenticApiRequest
{
    public static function matches(Request $request): bool
    {
        $path = trim($request->path(), '/');

        $prefixes = array_unique(array_filter([
            trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/'),
            trim((string) config('agentic.widget.prefix', 'api/agentic/widget'), '/'),
            trim((string) config('agentic.api.prefix', 'api/agentic'), '/'),
            trim((string) config('agentic.auth.prefix', 'api/agentic/auth'), '/'),
            trim((string) config('agentic.channels.prefix', 'api/agentic/channels'), '/'),
        ]));

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
