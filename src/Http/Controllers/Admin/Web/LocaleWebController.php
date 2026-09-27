<?php

namespace Agentic\Http\Controllers\Admin\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleWebController
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', config('agentic.admin.locales', ['en', 'ar']))],
        ]);

        session(['agentic.admin.locale' => $validated['locale']]);

        return back();
    }
}
