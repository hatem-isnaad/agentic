<!DOCTYPE html>
<html lang="{{ $adminHtmlLang ?? 'en' }}" dir="{{ $adminDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agentName }} — {{ __('agentic::admin.widget.title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --w-bg: #0b1220;
            --w-panel: #111827;
            --w-border: #1f2937;
            --w-text: #f3f4f6;
            --w-muted: #9ca3af;
            --w-primary: #3b82f6;
            --w-primary-hover: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Cairo', system-ui, sans-serif;
            background: linear-gradient(160deg, #0b1220 0%, #111827 100%);
            color: var(--w-text);
            min-height: 100vh;
        }
        #app {
            max-width: 440px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            border-inline: 1px solid var(--w-border);
            background: var(--w-bg);
        }
        header {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--w-border);
            background: var(--w-panel);
        }
        header strong { font-size: 1.05rem; font-weight: 700; }
        .meta { font-size: 0.8rem; color: var(--w-muted); margin-top: 0.25rem; }
        #messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .bubble {
            padding: 0.7rem 0.9rem;
            border-radius: 14px;
            max-width: 92%;
            white-space: pre-wrap;
            line-height: 1.5;
            font-size: 0.92rem;
        }
        .user {
            align-self: flex-end;
            background: var(--w-primary);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        [dir="rtl"] .user { border-bottom-right-radius: 14px; border-bottom-left-radius: 4px; }
        .assistant {
            align-self: flex-start;
            background: var(--w-panel);
            border: 1px solid var(--w-border);
            border-bottom-left-radius: 4px;
        }
        [dir="rtl"] .assistant { border-bottom-left-radius: 14px; border-bottom-right-radius: 4px; }
        form {
            display: flex;
            gap: 0.5rem;
            padding: 0.85rem;
            border-top: 1px solid var(--w-border);
            background: var(--w-panel);
        }
        input {
            flex: 1;
            font-family: inherit;
            font-size: 0.95rem;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            border: 1px solid var(--w-border);
            background: var(--w-bg);
            color: var(--w-text);
        }
        button {
            font-family: inherit;
            font-weight: 600;
            padding: 0.65rem 1.1rem;
            border-radius: 10px;
            border: none;
            background: var(--w-primary);
            color: #fff;
            cursor: pointer;
        }
        button:hover { background: var(--w-primary-hover); }
        button:disabled { opacity: 0.55; cursor: not-allowed; }
    </style>
</head>
<body>
<div id="app">
    <header>
        <strong>{{ $agentName }}</strong>
        <div class="meta">{{ __('agentic::admin.widget.agent_label') }}: {{ $agentSlug }}</div>
    </header>
    <div id="messages" aria-live="polite"></div>
    <form id="composer">
        <input type="text" id="input" placeholder="{{ __('agentic::admin.widget.placeholder') }}" autocomplete="off" required>
        <button type="submit" id="send">{{ __('agentic::admin.widget.send') }}</button>
    </form>
</div>
<script>
(() => {
    const api = @json($widgetApiPrefix);
    const agent = @json($agentSlug);
    const initialConversationId = @json($initialConversationId ?? null);
    const guestKey = 'agentic_guest_id';
    let guestId = localStorage.getItem(guestKey);
    if (!guestId) {
        guestId = crypto.randomUUID();
        localStorage.setItem(guestKey, guestId);
    }
    let conversationId = initialConversationId || null;
    const messages = document.getElementById('messages');
    const form = document.getElementById('composer');
    const input = document.getElementById('input');
    const sendBtn = document.getElementById('send');

    function stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    function resolveAssistantReply(data) {
        if (typeof data.text === 'string' && data.text.trim() !== '') {
            return { text: data.text, html: null };
        }
        const msg = data.message;
        if (msg && typeof msg === 'object') {
            if (typeof msg.html === 'string' && msg.html.trim() !== '') {
                return { text: stripHtml(msg.html), html: msg.html };
            }
            if (Array.isArray(msg.blocks)) {
                const text = msg.blocks
                    .map((b) => (b && (b.text || b.html || '')) || '')
                    .filter(Boolean)
                    .join('\n\n');
                if (text.trim() !== '') {
                    return { text, html: null };
                }
            }
        }
        if (typeof data.message === 'string' && data.message.trim() !== '') {
            return { text: data.message, html: null };
        }
        if (typeof data.reply === 'string' && data.reply.trim() !== '') {
            return { text: data.reply, html: null };
        }
        return { text: JSON.stringify(data), html: null };
    }

    function addBubble(role, text, html) {
        const el = document.createElement('div');
        el.className = 'bubble ' + role;
        if (role === 'assistant' && html) {
            el.innerHTML = html;
        } else {
            el.textContent = text;
        }
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
    }

    async function loadExistingMessages() {
        if (!conversationId) return;
        try {
            const res = await fetch(api + '/conversations/' + encodeURIComponent(conversationId) + '/messages', {
                headers: { 'Accept': 'application/json', 'X-Agentic-Guest-Id': guestId },
            });
            const json = await res.json();
            if (!res.ok) return;
            const list = json.data || [];
            messages.innerHTML = '';
            list.forEach((msg) => {
                const role = msg.role === 'user' ? 'user' : 'assistant';
                const html = msg.html || '';
                const text = html ? stripHtml(html) : (msg.text || '');
                addBubble(role, text, role === 'assistant' ? html : null);
            });
        } catch (_) { /* ignore */ }
    }

    loadExistingMessages();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        addBubble('user', text);
        sendBtn.disabled = true;
        try {
            const body = { agent, message: text };
            if (conversationId) body.conversation_id = conversationId;
            const res = await fetch(api + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Agentic-Guest-Id': guestId,
                },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (!res.ok) {
                addBubble('assistant', json.message || JSON.stringify(json));
                return;
            }
            const data = json.data || {};
            conversationId = data.conversation_id || conversationId;
            const reply = resolveAssistantReply(data);
            addBubble('assistant', reply.text, reply.html);
        } catch (err) {
            addBubble('assistant', 'Error: ' + err.message);
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    });
})();
</script>
</body>
</html>
