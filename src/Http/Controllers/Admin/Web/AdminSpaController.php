<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Support\AdminSpaAssets;
use Illuminate\Contracts\View\View;

final class AdminSpaController
{
    public function __invoke(): View
    {
        $meta = AdminLocaleMeta::build();
        $apiPrefix = '/'.trim((string) config('agentic.admin.api.prefix', 'api/agentic/admin'), '/');
        $webPrefix = '/'.trim((string) config('agentic.admin.web.prefix', 'agentic/admin'), '/');

        return view('agentic::admin.spa', [
            'assets' => AdminSpaAssets::resolve(),
            'boot' => [
                'apiPrefix' => $apiPrefix,
                'webPrefix' => $webPrefix,
                'locale' => $meta['locale'],
                'direction' => $meta['direction'],
                'locales' => config('agentic.admin.locales', ['en', 'ar']),
                'rtlLocales' => config('agentic.admin.rtl_locales', ['ar']),
            ],
        ]);
    }
}
