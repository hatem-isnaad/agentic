<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocaleController
{
    public function show(): JsonResponse
    {
        return response()->json(['meta' => AdminLocaleMeta::build()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', config('agentic.admin.locales', ['en', 'ar']))],
        ]);

        app()->setLocale($validated['locale']);

        return response()->json(['meta' => AdminLocaleMeta::build()]);
    }
}
