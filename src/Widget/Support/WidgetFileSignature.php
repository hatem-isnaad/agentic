<?php

namespace Agentic\Widget\Support;

use Illuminate\Http\Request;

final class WidgetFileSignature
{
    public static function isAttachmentRoute(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $name = $request->route()?->getName();
        if (is_string($name) && str_ends_with($name, 'conversations.files.show')) {
            return true;
        }

        $prefix = trim((string) config('agentic.widget.prefix', 'api/agentic/widget'), '/');

        return (bool) preg_match(
            '#^'.preg_quote($prefix, '#').'/conversations/[0-9a-f-]{36}/files/.+#i',
            $request->path(),
        );
    }

    public static function isValid(Request $request): bool
    {
        if (! self::isAttachmentRoute($request)) {
            return false;
        }

        if ($request->hasValidRelativeSignature()) {
            return true;
        }

        if ($request->hasValidSignature(absolute: true)) {
            return true;
        }

        return $request->hasValidRelativeSignatureWhileIgnoring();
    }
}
