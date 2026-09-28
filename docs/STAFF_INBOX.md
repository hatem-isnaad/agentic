# Custom staff inbox

The package inbox at `/agentic/admin/inbox` is optional. You can build the same desk in **your** admin (Laravel, React, Vue, mobile) and never open Agentic’s UI.

Agentic only stores conversations and exposes JSON. Layout, roles, and branding stay in your app.

**Related**

| File | Use |
|------|-----|
| [`FRONTEND_IMPLEMENTATION_GUIDE.md`](./FRONTEND_IMPLEMENTATION_GUIDE.md) | All admin/widget routes and headers; widget renders **full** `message.created` (stream off by default) |
| [`WIDGET_EMBED_SDK.md`](./WIDGET_EMBED_SDK.md) | Customer chat popup |
| [`DEVELOPER_HANDBOOK.md`](./DEVELOPER_HANDBOOK.md) | `AGENTIC_WIDGET_HANDOFF`, `AGENTIC_WIDGET_STREAM`, admin auth |

---

## What you are building

Customer chat (widget or WhatsApp) talks to the **agent** until the thread is converted to a **person**.

| Status | Meaning | Agent auto-reply |
|--------|---------|------------------|
| `none` | Normal AI chat | Yes |
| `requested` | Customer (or the agent offer) asked for a person | No |
| `taken` | A staff member owns the thread | No |

`needs_human` is `true` when status is `requested` or `taken`.

The customer does **not** need a “Talk to a person” button. The agent offers Yes/No in the chat when it cannot finish. The customer can tap **Approve** on the in-chat card or type a short `yes` / `موافق`. After that, queued AI jobs skip the thread.

Your UI only needs to: list threads, open messages, take, reply, release.

---

## Auth

Staff calls use the **admin API**, not the widget embed token.

**Base:** `{APP_URL}/api/agentic/admin`  
(`AGENTIC_ADMIN_API_PREFIX`, default `api/agentic/admin`)

| Header | When |
|--------|------|
| `Accept: application/json` | Always |
| `X-Agentic-Locale: en` or `ar` | Labels / RTL meta |
| `Content-Type: application/json` | POST bodies |
| Session cookie + CSRF | Same-site Laravel login |
| `Authorization: Bearer {token}` | Sanctum personal access token |

Production must lock this API:

- `AGENTIC_ADMIN_REQUIRE_AUTH=true` (package default)
- `AGENTIC_ADMIN_GATE=viewAgentic` and `Gate::define('viewAgentic', …)` on **your** users

You decide who is “staff”. Agentic does not ship a helpdesk user table.

You can set `AGENTIC_ADMIN_WEB_ENABLED=false` and still keep the JSON API (`AGENTIC_ADMIN_API_ENABLED=true`).

---

## APIs (staff)

All paths below are under `/api/agentic/admin`.

| Method | Path | Body / query | What it does |
|--------|------|----------------|--------------|
| `GET` | `/inbox` | `limit` (default 80, how many recent threads), `page`, `per_page`, `q` | Thread list. Human threads first. |
| `GET` | `/conversations/{id}` | — | One thread + `metadata.handoff` |
| `GET` | `/conversations/{id}/messages` | `limit` (1–100, default 50) | Chronological chat (no `system` rows) |
| `POST` | `/conversations/{id}/take` | `{ "by": "sara@example.com" }` optional | Mark `taken`. `by` defaults to the logged-in email. |
| `POST` | `/conversations/{id}/reply` | `{ "message": "…" }` max 8000 | Take if needed, store a staff line, push it to the widget. **201** |
| `POST` | `/conversations/{id}/release` | — | Back to `none`. The agent can reply again. |

`GET /conversations` is a flat history list (not sorted for a desk). Prefer `GET /inbox` for the left pane.

### Inbox row

```json
{
  "id": "uuid",
  "agent": "support",
  "user_id": null,
  "metadata": { "handoff": { "status": "requested", "by": "widget", "at": "…" } },
  "preview": "Last message text…",
  "last_role": "user",
  "last_message_at": "2026-09-28T06:26:03.000000Z",
  "handoff_status": "requested",
  "needs_human": true,
  "created_at": "…",
  "updated_at": "…"
}
```

List envelope:

```json
{
  "data": [ "…" ],
  "meta": { "total": 8, "page": 1, "per_page": 25, "last_page": 1, "locale": "en", "direction": "ltr" }
}
```

### Message row

```json
{
  "id": "uuid",
  "cursor": 42,
  "role": "user",
  "html": "Where is my order?",
  "format": "html",
  "blocks": null,
  "created_at": "…",
  "source": null
}
```

`role` is `user` (customer) or `assistant` (agent **or** staff). Staff replies set `source` to `staff`.

Render `user` on the start side and `assistant` on the end side. Strip tags for a WhatsApp-style bubble, or render `html` / `blocks` if you want rich agent cards.

---

## Customer side (already in the widget)

You do **not** rebuild this unless you wrote your own chat UI.

| Method | Path | When |
|--------|------|------|
| `POST` | `/api/agentic/widget/conversations/{id}/handoff` | Convert without the in-chat Yes card |
| `POST` | `/api/agentic/widget/approvals/{id}/approve` | Customer taps **Approve** on the handoff card |
| `POST` | `/api/agentic/widget/messages` | After handoff returns `{ "handoff": true }` and does **not** queue the agent |

Enable with `AGENTIC_WIDGET_HANDOFF=true`.

---

## Live updates

The built-in admin inbox **polls**. A custom desk should do the same:

1. `GET /inbox?per_page=100` every 3–5 seconds
2. `GET /conversations/{id}/messages?limit=80` every 2–3 seconds while a thread is open

Customer lines are stored but not always pushed on Pusher. Polling is the reliable way to see them.

Optional: subscribe to Pusher channel `agentic-widget.{conversationId}` (same as the widget). Event `message.created` fires for **full** agent replies and **staff** replies (token `message.delta` is off by default — do not build a streaming inbox). Still poll for customer text. Customer images arrive as `html` with a signed file URL.

---

## Suggested UI (your app)

1. **Left:** threads from `GET /inbox`. Show `agent`, `preview`, `last_message_at`, badge when `needs_human`.
2. **Right:** messages from `GET …/messages`. Customer start, staff/agent end.
3. **Header:** Take / Return to agent (`take` / `release`).
4. **Composer:** text + Send → `POST …/reply`. Enter sends. Reply also takes the thread.
5. After `release`, new customer messages queue the agent again (`php artisan queue:work` must be running).

You can hide `/agentic/admin/inbox` entirely. The APIs stay.

---

## curl

```bash
# List desk threads (session cookie or Bearer)
curl -sS "$APP/api/agentic/admin/inbox?per_page=50" \
  -H "Accept: application/json" \
  -H "X-Agentic-Locale: en"

# Open a thread
curl -sS "$APP/api/agentic/admin/conversations/$ID/messages?limit=80" \
  -H "Accept: application/json"

# Take + reply (reply takes automatically)
curl -sS -X POST "$APP/api/agentic/admin/conversations/$ID/reply" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"message":"I am here. What is the order number?"}'

# Give the thread back to the agent
curl -sS -X POST "$APP/api/agentic/admin/conversations/$ID/release" \
  -H "Accept: application/json"
```

Same-origin browser:

```js
const prefix = '/api/agentic/admin';

async function inbox() {
  const res = await fetch(`${prefix}/inbox?per_page=100`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-Agentic-Locale': 'en' },
  });
  return res.json(); // { data, meta }
}

async function reply(conversationId, message) {
  const res = await fetch(`${prefix}/conversations/${conversationId}/reply`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Agentic-Locale': 'en',
    },
    body: JSON.stringify({ message }),
  });
  return res.json(); // 201 { data: { id, role, html, conversation_id } }
}
```

---

## PHP in your host (no HTTP)

If your desk already lives in the same Laravel app, call the same services your controllers use:

```php
use Agentic\Conversation\ConversationHandoffService;
use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\Services\WidgetConversationService;

$handoff = app(ConversationHandoffService::class);

$threads = $handoff->inbox(80);          // human threads first
$handoff->isHumanId($conversationId);    // skip AI if true
$handoff->take($conversationId, $user->email);
$handoff->release($conversationId);

$messages = app(WidgetConversationService::class)
    ->messagesPage($conversationId, new WidgetIdentity(null, null), 80);
```

Staff **reply** (store + broadcast to the widget) is `InboxController::reply`. Reuse that HTTP route, or copy its store + `message.created` publish. Do not invent a second message table.

---

## Checklist

- [ ] Admin API enabled and gated to your staff
- [ ] `AGENTIC_WIDGET_HANDOFF=true`
- [ ] `queue:work` running (async widget + skip-if-human)
- [ ] Left list = `GET /inbox`, not only `GET /conversations`
- [ ] Poll list + open thread
- [ ] Reply uses `POST …/reply` so the customer sees it in the widget
- [ ] Release when the person is done, or the agent stays off
