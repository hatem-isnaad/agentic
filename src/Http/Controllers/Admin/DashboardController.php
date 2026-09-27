<?php

namespace Agentic\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;

final class DashboardController
{
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();
        $rtl = in_array($locale, config('agentic.admin.rtl_locales', ['ar']), true);

        return response()->json([
            'data' => [
                'stats' => [
                    'agents' => 0,
                    'skills' => 0,
                    'tools' => 0,
                    'knowledge_sources' => 0,
                    'executions' => 0,
                    'conversations' => 0,
                ],
            ],
            'meta' => [
                'locale' => $locale,
                'direction' => $rtl ? 'rtl' : 'ltr',
                'html_lang' => $locale,
                'is_rtl' => $rtl,
                'supported_locales' => config('agentic.admin.locales', ['en', 'ar']),
            ],
        ]);
    }
}
