@props([
    'agent' => config('agentic.widget.embed.default_agent'),
    'token' => config('agentic.widget.embed.token') ?: config('services.agentic.widget_embed_token'),
    'api' => null,
    'theme' => 'system',
    'position' => config('agentic.widget.embed.position', 'bottom-right'),
    'sounds' => true,
    'open' => false,
    'conversation' => null,
])

@php
    $assets = \Agentic\Support\WidgetEmbedAssets::resolve();
    $apiBase = $api ?? url('/'.trim((string) config('agentic.widget.prefix', 'api/agentic/widget'), '/'));
@endphp

@if ($assets['css'])
    <link rel="stylesheet" href="{{ $assets['css'] }}">
@endif

<div data-agentic-widget-api="{{ $apiBase }}" aria-hidden="true"></div>

<script src="{{ $assets['js'] }}" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.AgenticChat) {
            console.error('Agentic widget script failed to load');
            return;
        }
        window.AgenticChat.init({
            agent: @json($agent),
            apiBase: @json($apiBase),
            embedToken: @json($token),
            theme: @json($theme),
            position: @json($position),
            sounds: @json((bool) $sounds),
            open: @json((bool) $open),
            conversationId: @json($conversation),
        });
    });
</script>
