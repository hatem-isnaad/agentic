<?php

namespace Agentic\Http\Support;

final class AdminLocaleMeta
{
    /**
     * @return array<string, mixed>
     */
    public static function build(?array $extra = null): array
    {
        $locale = app()->getLocale();
        $rtlLocales = config('agentic.admin.rtl_locales', ['ar']);
        $isRtl = in_array($locale, $rtlLocales, true);

        $meta = [
            'locale' => $locale,
            'direction' => $isRtl ? 'rtl' : 'ltr',
            'html_lang' => $locale,
            'is_rtl' => $isRtl,
            'supported_locales' => config('agentic.admin.locales', ['en', 'ar']),
        ];

        return $extra === null ? $meta : array_merge($meta, $extra);
    }
}
