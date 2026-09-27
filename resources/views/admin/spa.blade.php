<!DOCTYPE html>
<html lang="{{ $boot['locale'] }}" dir="{{ $boot['direction'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('agentic::admin.app_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if ($assets['style'])
        <link rel="stylesheet" href="{{ $assets['style'] }}">
    @endif
</head>
<body class="antialiased">
    <div id="agentic-admin-root"></div>
    <script>
        window.__AGENTIC_ADMIN__ = @json($boot);
    </script>
    @if ($assets['script'])
        <script type="module" src="{{ $assets['script'] }}"></script>
    @endif
</body>
</html>
