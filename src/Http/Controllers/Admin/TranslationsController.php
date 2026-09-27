<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;

final class TranslationsController
{
    public function __invoke(): JsonResponse
    {
        $tree = [];
        foreach (config('agentic.admin.locales', ['en', 'ar']) as $locale) {
            $tree[$locale] = Lang::get('agentic::admin', [], $locale);
        }

        return response()->json([
            'data' => $tree,
            'meta' => AdminLocaleMeta::build(),
        ]);
    }
}
