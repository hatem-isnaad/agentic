<!DOCTYPE html>
<html lang="{{ $adminHtmlLang ?? 'en' }}" dir="{{ $adminDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agentName }} — {{ __('agentic::admin.widget.title') }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #0b1220;
            color: #e5e7eb;
            font-family: system-ui, sans-serif;
        }
        .preview {
            padding: 1.5rem 1.25rem;
        }
        .preview strong { display: block; font-size: 1.05rem; }
        .preview p { margin: 0.4rem 0 0; color: #9ca3af; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="preview">
        <strong>{{ $agentName }}</strong>
        <p>{{ __('agentic::admin.widget.agent_label') }}: {{ $agentSlug }}</p>
    </div>
    <x-agentic-widget
        :agent="$agentSlug"
        :token="$embedToken !== '' ? $embedToken : null"
        :conversation="$initialConversationId"
        :open="true"
        theme="system"
        position="bottom-right"
        :sounds="true"
    />
</body>
</html>
