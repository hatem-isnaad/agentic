# Widget embed SDK (popup chat for Blade, SPA, any site)

Env, tokens, and limits: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) §5.

Drop a **chat popup** on any webpage: standalone JS/CSS, themes (light / dark / system / brand), optional sounds, and **production security** so random sites cannot use your widget even if they copy your script tags.

## Quick start — embed on your website

### Step 1 · Publish widget files (once per deploy)

```bash
# From your Laravel app root (built JS/CSS ship in the Composer package):
php artisan vendor:publish --tag=agentic-widget-assets --force
```

To rebuild after editing widget source in this repo: `npm run build:widget`, then publish again. The reference host also exposes `npm run build:widget`.

Your site serves:

- `/vendor/agentic/widget/agentic-widget.js`
- `/vendor/agentic/widget/agentic-widget.css`

### Step 2 · Create an embed secret + lock domains

Each **embed token** is a site API secret (`wgt_…`). You configure **which agent(s)** it may call and **which origins** (full site URLs) may use it.

**Admin:** `/agentic/admin/widget-embed-tokens` — plain `wgt_…` is shown **once** on create.

**CLI:**

```bash
php artisan agentic:widget-embed-token create \
  --name=production \
  --agents=support \
  --origins=https://www.your-company.com,https://app.your-company.com
```

Store `wgt_…` in server config (`.env`, secrets manager). **Do not commit** it to git.

Enable enforcement in production:

```env
AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true
```

The embed **does not mount** the launcher or panel until `GET /config?agent=…` succeeds with a valid `wgt_…` (or Sanctum `bearerToken`). Placeholders such as `INJECT_FROM_SERVER` are rejected in the browser — nothing is shown. `AgenticChat.init()` returns a `Promise` (`null` when validation fails).

### Step 3 · Paste this on allowed pages only

**Laravel Blade (recommended)** — token never lives in a static HTML file; Blade injects it at render time:

```blade
{{-- config/services.php: 'agentic' => ['widget_embed_token' => env('AGENTIC_WIDGET_EMBED_TOKEN')] --}}
<x-agentic-widget
    agent="support"
    :token="config('services.agentic.widget_embed_token')"
    theme="system"
    position="bottom-right"
    :sounds="true"
/>
```

Component: `agentic::components.widget-embed` (alias `<x-agentic-widget>` when registered).

**Any HTML / React / Vue / WordPress** — same assets; inject the token from **your backend** into the page (SSR, short-lived config endpoint, or server-rendered template). Avoid checking `wgt_…` into a public Git repo.

```html
<link rel="stylesheet" href="https://your-app.com/vendor/agentic/widget/agentic-widget.css">
<script src="https://your-app.com/vendor/agentic/widget/agentic-widget.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    void AgenticChat.init({
      agent: 'support',
      apiBase: 'https://your-app.com/api/agentic/widget',
      embedToken: 'INJECT_FROM_SERVER', // wgt_… from env — not a separate "public key"
      theme: 'system',
      position: 'bottom-right',
      sounds: true,
    });
  });
</script>
```

After the first message, the client sends a per-browser `X-Agentic-Guest-Id` when the token allows guests.

---

## External sites — files to download, URLs, and full HTML snippet

Use this section when the chat runs on a **different website** (WordPress, static marketing site, React app on `www.example.com`) while the **Agentic API** stays on your Laravel host (`api.example.com` or the same app).

### What you ship (no HTML template file)

The popup UI is created by JavaScript. There is **no** separate `.html` partial to download — only two static assets:

| File | Purpose |
|------|---------|
| `agentic-widget.js` | IIFE bundle; exposes `window.AgenticChat.init()` |
| `agentic-widget.css` | Launcher + panel styles |

Sounds and icons are built into the JS (no extra image URLs).

### Where the files live

| Location | Path |
|----------|------|
| **After `vendor:publish`** (what browsers load) | `public/vendor/agentic/widget/agentic-widget.js` and `.css` |
| **After `npm run build:widget`** (package output, before publish) | `vendor/hatem-isnaad/agentic/resources/dist/widget/` (or `agentic/resources/dist/widget/` in the package repo) |

**Public URLs** (replace host with yours):

```text
https://YOUR-LARAVEL-HOST/vendor/agentic/widget/agentic-widget.js
https://YOUR-LARAVEL-HOST/vendor/agentic/widget/agentic-widget.css
```

Optional: set `AGENTIC_WIDGET_EMBED_SCRIPT_URL=https://cdn.example.com/agentic/widget` so Blade `<x-agentic-widget>` points CSS/JS at your CDN (you must upload the same two files there).

### Download / copy for self-hosting (CDN, S3, Netlify, etc.)

From your machine (after assets are published on Laravel):

```bash
BASE=https://YOUR-LARAVEL-HOST/vendor/agentic/widget
curl -fsSL -o agentic-widget.js  "$BASE/agentic-widget.js"
curl -fsSL -o agentic-widget.css "$BASE/agentic-widget.css"
```

Or copy from the server filesystem:

```bash
# On the Laravel server
cp public/vendor/agentic/widget/agentic-widget.{js,css} /path/to/your/static/site/
```

Re-copy after every deploy that runs `npm run build:widget` and `vendor:publish --tag=agentic-widget-assets`.

### Architecture (external page → your API)

```text
┌─────────────────────────────────────┐     HTTPS JSON      ┌──────────────────────────────────┐
│  External site                      │ ──────────────────► │  Laravel (Agentic widget API)    │
│  https://www.example.com            │   apiBase + wgt_…     │  https://api.example.com         │
│  <script> AgenticChat.init(...)     │   Origin: www…        │  /api/agentic/widget/*           │
└─────────────────────────────────────┘                     └──────────────────────────────────┘
         ▲
         │ JS/CSS from Laravel host OR your CDN (same files)
```

1. **Embed token `allowed_origins`** must include the **page** origin (e.g. `https://www.example.com`), not only the API host.
2. **`apiBase`** must be the full widget API prefix on Laravel, e.g. `https://api.example.com/api/agentic/widget`.
3. **`embedToken`** (`wgt_…`) must come from **your server** (env, SSR, edge function) — never commit it in the static site repo.
4. If the page origin and API host differ, configure Laravel **CORS** (`config/cors.php`) so `paths` include `api/agentic/widget/*` and `allowed_origins` includes your external site.

### Copy-paste: minimal HTML for an external site

Replace the three placeholders: `YOUR_API_HOST`, `YOUR_AGENT_SLUG`, and inject `YOUR_EMBED_TOKEN` server-side.

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My site with Agentic chat</title>
  <!-- Option A: assets from Laravel host -->
  <link rel="stylesheet" href="https://YOUR_API_HOST/vendor/agentic/widget/agentic-widget.css">
</head>
<body>
  <h1>Welcome</h1>

  <script src="https://YOUR_API_HOST/vendor/agentic/widget/agentic-widget.js" defer></script>
  <!-- Option B: self-hosted CDN — same filenames, different href/src -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.AgenticChat) {
        console.error('agentic-widget.js failed to load');
        return;
      }
      window.AgenticChat.init({
        agent: 'YOUR_AGENT_SLUG',
        apiBase: 'https://YOUR_API_HOST/api/agentic/widget',
        embedToken: 'YOUR_EMBED_TOKEN', // inject from server — wgt_…
        theme: 'system',
        position: 'bottom-right',
        sounds: true,
        debug: false,
      });
    });
  </script>
</body>
</html>
```

There is **no** required markup in `<body>` for the chat — the script appends the launcher and panel to `document.body`.

### `AgenticChat.init` options (external integrators)

| Option | Required | Description |
|--------|----------|-------------|
| `agent` | Yes | Published agent slug (`support`, …) |
| `apiBase` | Recommended | Full URL to widget API prefix |
| `embedToken` | When `AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true` | `wgt_…` from embed token create |
| `bearerToken` | Optional | Sanctum PAT for logged-in users |
| `theme` | Optional | Preset id (`isnaad`, `techsup`, `ocean`, …) or `light` / `dark` / `system` / `brand`. No picker in the UI. |
| `position` | Optional | `bottom-right` (default), `bottom-left`, `top-right`, `top-left` |
| `sounds` | Optional | `true` / `false` |
| `open` | Optional | Open panel on load |
| `conversationId` | Optional | Resume a specific conversation UUID |
| `debug` | Optional | Console logs for Pusher subscribe |

### React / Vue / Next.js

Load the script once (dynamic import or `<script>` in document). After `AgenticChat` exists:

```javascript
window.AgenticChat.init({
  agent: 'support',
  apiBase: process.env.NEXT_PUBLIC_AGENTIC_WIDGET_API,
  embedToken: process.env.AGENTIC_WIDGET_EMBED_TOKEN, // server-only env, passed from getServerSideProps / loader
});
```

Do not put `wgt_…` in `NEXT_PUBLIC_*` unless you accept it being visible in the client bundle.

### Verify assets before go-live

```bash
curl -I https://YOUR_API_HOST/vendor/agentic/widget/agentic-widget.js   # expect 200
curl -I https://YOUR_API_HOST/vendor/agentic/widget/agentic-widget.css  # expect 200
curl -s "https://YOUR_API_HOST/api/agentic/widget/config?agent=support" \
  -H "Authorization: Bearer wgt_…" \
  -H "Origin: https://www.example.com" | head
```

---

## Simple model (one token, one validator)

1. Create an embed token in **Admin → Embed tokens** (`allowed_origins`, `allowed_agents`, **guest allowed** on/off).
2. Page calls `AgenticChat.init({ embedToken: 'wgt_…', agent, apiBase, bearerToken? })`.
3. **No UI** until `GET /config?agent=…` succeeds (token in DB, host/origin match, guest header or Sanctum session).
4. **Every** widget request (`/messages`, history, …) runs the same check again — no bypass.

| Token setting | Client |
|---------------|--------|
| Guest allowed | Auto `X-Agentic-Guest-Id` |
| Guests off (auth required) | Signed-in Sanctum session or `bearerToken` — **not** `X-Agentic-User-Id` |

Optional: `AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=false` only for local open API (not production).

---

## How security works (domain lock + embed secret)

| What | Public? | Role |
|------|---------|------|
| `agentic-widget.js` / `.css` | Yes — anyone can download | UI only; **cannot** call your API without credentials |
| Embed secret `wgt_…` | **No** — treat like an API key | Sent as `Authorization: Bearer wgt_…` (or `X-Agentic-Embed-Token`) on **every** widget API request |
| Allowed origins on the token | Config in admin/CLI | Server checks `Origin` / `Referer`; requests from other domains get **401 Origin not allowed** |
| Agent allowlist on the token | Config | Widget cannot switch to agents you did not authorize |

There is **no separate public/private key pair** in the embed flow today: the **`wgt_…` secret is the credential**. The JS bundle is public by design; security is **secret + origin allowlist + require_token**.

### What attackers **cannot** do (when configured correctly)

- Host your widget on **another domain** and chat with your backend — blocked by **allowed origins**, even if they stole your `wgt_…` from view-source.
- Use your token to hit **admin** or non-widget routes — embed tokens only apply to widget API middleware.
- Impersonate **other agents** — blocked if the token’s `allowed_agents` list is tight.

### What you should still assume

- On **your** allowed domain, anyone who can view the page can see the token in DevTools (same as any client-side API key). Mitigations: tight origins, rotate/revoke tokens, `--no-guest` for sensitive agents, Sanctum for logged-in users only, rate limits at the proxy.

### Production checklist

- `AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true`
- Every token has explicit `allowed_origins` (no empty “allow all” in production)
- One token per environment (staging vs production)
- Revoke compromised tokens in Admin → Embed tokens
- Optional: Sanctum PAT after login (`bearerToken` in `AgenticChat.init`) with `sanctum_allowed` on the token

---

## API headers (widget routes)

When `require_token` is enabled, each request needs:

- `Authorization: Bearer wgt_…` (or `X-Agentic-Embed-Token`)
- `X-Agentic-Guest-Id` (UUID per browser) when guests are allowed on the token
- Or Sanctum session / PAT when `sanctum_allowed` on the token

Middleware: `AuthenticateWidgetEmbed` validates token hash, expiry, and origin.

---

## Configuration (`config/agentic.php` → `widget.embed`)

| Key | Purpose |
|-----|---------|
| `require_token` | All widget API calls need embed token or Sanctum |
| `default_agent` | Default for Blade component |
| `position` | Launcher corner |
| `theme_presets` | Set in `AgenticChat.init({ theme })` only (no picker in the chat UI): aurora, midnight, ocean, forest, sunset, rose, gold, arctic, graphite, ember, slate, sand, lime, coral, indigo, mocha, mint, crimson, sky, neon, **isnaad** (Limenos / [isnaad.ai](https://www.isnaad.ai/en/) brand red `#c02526`, from [limenos.ai/login](https://limenos.ai/login)), **techsup** ([TechSup Tkt](https://techsup-tkt.com/) plum `#6C075D`). Aliases: light, dark, brand, system |
| `sound_pack` | subtle built-in beeps (no external files) |

Per-agent UI (welcome, intake, theme JSON): `PUT /api/agentic/admin/widget-settings/{agent}` or `WidgetSettingsService` in PHP.

## Message history (on open)

When the user opens the chat panel (after `GET /config` succeeds):

1. Loads `GET /conversations?agent=…` (preview + `last_message_at`).
2. If the **latest** conversation was active within `conversation.resume_after_hours` (default **24**), resume it. Otherwise start a **new** chat automatically (refresh included).
3. Fetches **one page** of history: `GET /conversations/{id}/messages?limit={page_size}`. Scrolling to the top loads older pages automatically.
4. Header **list** opens previous conversations in a **side drawer** over the chat (chat stays visible). **New conversation** creates a new server thread (not the latest one). Refresh keeps the thread you were in.

Limits: `history.page_size` (20), `history.max_page_size` (50). Env: `AGENTIC_WIDGET_HISTORY_PAGE_SIZE`, `AGENTIC_WIDGET_HISTORY_MAX_PAGE_SIZE`, `AGENTIC_WIDGET_RESUME_HOURS`, `AGENTIC_WIDGET_MAX_CONVERSATIONS`.

## Replies on web (HTML, not WhatsApp)

The widget is a **web page**. Assistant Markdown (`**bold**`, lists, tables, code) is converted to **safe HTML** on the server (`MarkdownHtmlConverter` + CommonMark) and rendered in the bubble with theme tokens. Arabic in the reply sets `dir="rtl"`. The composer has an **emoji** button next to Send.

Other channels (WhatsApp, Messenger) use `ChannelReplyPresenterFactory` — text markers only, no HTML. Do not treat web replies as WhatsApp markup.

## Lean context

Default on. Each turn sends a small subset of skills, tools, RAG chunks, and history — not the whole agent catalog. Widget limits are stricter than admin (`agentic.widget.context`). See README and `config/agentic.php`.

## Widget config API (minimal)

`GET /api/agentic/widget/config?agent={slug}` returns only what the chat UI needs:

| Field | Purpose |
|-------|---------|
| `agent.slug`, `agent.name` | Header label |
| `theme.mode`, `theme.custom` | Launcher + panel colors |
| `locale` | UI locale |
| `welcome` | Optional first message |
| `realtime` | Driver-specific connection info (no secrets) |

No AI provider list, admin meta, or server secrets in this response.

## Typing + async replies + Pusher (recommended)

**Flow when `AGENTIC_WIDGET_BROADCAST_DRIVER=pusher`:**

1. User sends → user bubble appears immediately.
2. `POST /messages` saves the user message and returns fast (`pending: true`, `conversation_id`).
3. Server broadcasts `assistant.typing` (`active: true`) on channel `{prefix}.{conversationId}`.
4. Background job runs the agent → saves assistant message → broadcasts `message.created` → `assistant.typing` (`active: false`).
5. Embed subscribes to that conversation channel (driver from config — no automatic fallback) and updates the UI.

Async is **automatic** with the Pusher driver (`AGENTIC_WIDGET_ASYNC_REPLIES` unset). Set `AGENTIC_WIDGET_ASYNC_REPLIES=false` to force sync HTTP replies even with Pusher.

**Reply vs result:** The HTTP `POST /messages` ack is only `pending` + `conversation_id` (fast). The user-facing assistant HTML arrives on Pusher `message.created` under `payload.message`. Token usage and `execution_id` are under `payload.result` (not rendered as chat text).

## Lean context (tokens + speed)

**Global (default on):** `agentic.context` + `AGENTIC_LEAN_CONTEXT=true` applies to every agent run (admin chat, API, widget). One “full system” agent can keep many skills/tools in the database; each turn routes a small subset.

**Widget (stricter):** `agentic.widget.context` when `channel=widget`.

Widget-specific env (override global when tighter):

| Env | Default | Effect |
|-----|---------|--------|
| `AGENTIC_WIDGET_LEAN_CONTEXT` | `true` | Enable widget policy |
| `AGENTIC_WIDGET_CONTEXT_HISTORY` | `12` | Prior user/assistant turns sent to the SDK (excludes the current user message) |
| `AGENTIC_WIDGET_SKILL_LIMIT` | `2` | Max skills when keyword routing matches |
| `AGENTIC_WIDGET_SKILLS_FALLBACK_LIMIT` | `2` | Max skills when **no** keyword match (avoids 100 skills) |
| `AGENTIC_WIDGET_KNOWLEDGE_LIMIT` | `3` | RAG chunks per turn |
| `AGENTIC_WIDGET_MEMORY_LIMIT` | `8` | Memory rows in prompt |
| `AGENTIC_WIDGET_MAX_TOOLS` | `20` | Cap tools registered for the turn |
| `AGENTIC_WIDGET_COMPACT_SKILLS` | `true` | Shorter skill blurbs in instructions |

For many tools, also enable deferred tool search: `AGENTIC_DEFERRED_TOOLS=true` (OpenAI/Anthropic).

```env
AGENTIC_WIDGET_BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=database   # or redis

PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=mt1
```

Run a worker: `php artisan queue:work`

**Polling driver:** sync HTTP by default; optional `AGENTIC_WIDGET_ASYNC_REPLIES=true` uses the same events over HTTP poll.

**Pusher driver:** WebSocket only — no HTTP polling fallback. Assistant replies are deduplicated by `message.id` (history + Pusher + optional sync HTTP). If the job finishes before subscribe, the client fetches the latest assistant message once on `subscription_succeeded` (no poll loop).

## Pusher realtime

In `.env`:

```env
AGENTIC_WIDGET_BROADCAST_DRIVER=pusher
AGENTIC_WIDGET_BROADCAST_PREFIX=agentic-widget

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
PUSHER_APP_CLUSTER=mt1
```

The PHP SDK is included with the package (`pusher/pusher-php-server` is a Composer dependency of `hatem-isnaad/agentic`). Set `PUSHER_*` in the host `.env`.

The embed client subscribes to public channel `{prefix}.{conversationId}` and listens for `message.created`. Config exposes only `realtime.pusher.key` and `cluster`.

**When does Pusher connect?** Only after a `conversation_id` exists (return visit with history, or after the first sent message). Open DevTools → **Network → WS** (not Fetch) to see the WebSocket to `*.pusher.com`. The chat header shows a green dot when subscribed.

Debug logging:

```javascript
AgenticChat.init({ agent: 'support', embedToken: '…', debug: true });
```

## Theme

- **Theme:** `PUT /api/agentic/admin/widget-settings/{agent}` with `theme.default` and `theme.custom`.

Optional **polling driver** (`AGENTIC_WIDGET_BROADCAST_DRIVER=polling`) loads a separate client chunk; it is never used when the server reports `realtime.driver: pusher`.

## Roadmap

- React/Vue wrapper packages
- Custom sound packs via URL
- Messenger live webhook (presenter exists; WhatsApp Meta + webjs are live — [CHANNELS.md](./CHANNELS.md))
