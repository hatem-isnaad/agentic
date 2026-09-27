<!DOCTYPE html>
<html lang="{{ $adminHtmlLang ?? 'en' }}" dir="{{ $adminDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('agentic::admin.app_title')) — {{ __('agentic::admin.app_title') }}</title>
    @include('agentic::admin.partials.admin-styles')
</head>
<body class="agentic-admin">
<div class="ag-shell">
    @include('agentic::admin.partials.nav')
    <div class="ag-main">
        <header class="ag-topbar">
            @include('agentic::admin.partials.locale-switcher')
        </header>
        <div class="ag-content">
            @include('agentic::admin.partials.flash')
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
