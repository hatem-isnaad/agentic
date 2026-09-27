<!DOCTYPE html>
<html lang="{{ $adminHtmlLang ?? 'en' }}" dir="{{ $adminDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('agentic::admin.app_title'))</title>
    @include('agentic::admin.partials.rtl-styles')
</head>
<body>
    @include('agentic::admin.partials.nav')
    @include('agentic::admin.partials.locale-switcher')

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
