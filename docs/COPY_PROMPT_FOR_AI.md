# Copy-paste prompt for AI agent (React implementation)

**Instructions:** Copy everything inside the fenced block below into a new Cursor/Claude chat when starting the Admin and/or Widget React projects. Attach or point the agent at `docs/FRONTEND_IMPLEMENTATION_GUIDE.md` in the `hatem-isnaad/agentic` repo for full detail.

**Last updated:** 2026-09-28

---

```markdown
You are implementing React frontends for the **Agentic** Laravel package (headless APIs only — no Filament, no package Blade UI in production).

## Your deliverables

1. **Admin SPA** — dashboard to manage agents, skills, tools, knowledge, executions, conversations, and per-agent widget settings (en/ar, RTL/LTR).
2. **Widget SPA** — embeddable chat widget (guest + auth), structured message blocks, tool approval UI, realtime updates.

Read the repo docs:
- `docs/FRONTEND_IMPLEMENTATION_GUIDE.md` — all routes, headers, payloads, events (SOURCE OF TRUTH)
- `docs/SYSTEM_DESIGN.md` — sequence diagrams and architecture
- `.env.example` — backend env naming

## API bases (defaults)

- Admin: `{VITE_API_BASE_URL}/api/agentic/admin`
- Widget: `{VITE_API_BASE_URL}/api/agentic/widget`

## Required headers

- Admin + widget locale: `X-Agentic-Locale: en|ar` (also support `?locale=`)
- Widget guest: `X-Agentic-Guest-Id: <persist in localStorage>`
- Widget user identity: Sanctum session / Bearer only (never `X-Agentic-User-Id`)
- Auth: use host Laravel Sanctum/session/Bearer as configured by the host app

## Admin API routes to implement (all under admin prefix)

- GET `/dashboard`, GET `/translations`, GET|PUT `/locale`
- CRUD `/agents`, `/skills`, `/tools`, `/knowledge-sources`
- POST `/agents/{slug}/execute` — body: `{ message, conversation_id?, metadata?, variables? }`
- POST `/knowledge-sources/{slug}/index`, POST `/knowledge-sources/{slug}/search` — body: `{ query, limit? }`
- GET `/widget-settings/schema`, CRUD `/widget-settings`, PUT `/widget-settings/{agentSlug}` — body: `{ settings: { auth_mode, intake_*, welcome_message, theme, reply_formats, ... } }`
- GET paginated `/executions`, `/conversations` — query: `page`, `per_page`, `q`, `status`, `agent`, `fetch_limit`
- Staff inbox (optional — or build in the host app): `GET /inbox`, `GET /conversations/{id}/messages`, `POST /conversations/{id}/take|reply|release` — see `docs/STAFF_INBOX.md`
- All list responses include `meta.total`, `meta.page`, `meta.per_page`, `meta.locale`, `meta.direction`, `meta.is_rtl`

## Widget API routes to implement

- GET `/config?agent={slug}` — theme, intake, locale, realtime driver, `stream` (default **false**), providers
- GET `/conversations?agent=`, POST `/conversations`
- POST `/messages` — `{ agent, message, conversation_id?, locale?, metadata? }`. With Pusher this returns `{ pending: true, conversation_id }` — **not** the assistant HTML.
- GET|POST `/conversations/{id}/messages` — history `html` may include signed `<img>` for uploads
- POST `/approvals/{id}/approve|reject|execute` — Approve/Reject in the official widget
- GET `/conversations/{id}/realtime?since_id=&limit=` when driver=polling
- GET `/conversations/{id}/files/{file}` — signed image/PDF (no guest header; URL is the auth)

## Realtime events (channel: `{prefix}.{conversationId}`, default prefix `agentic-widget`)

- `assistant.typing` — `{ active }` while the queue job runs
- `message.created` — **render this** (`payload.message.html` + optional `blocks`). Staff inbox replies use the same event.
- `message.resumed` — after approval auto-resume (same shape)
- `message.delta` — token fragments **only** if `GET /config` has `stream: true` (`AGENTIC_WIDGET_STREAM=true`). Default is off; **ignore deltas** and wait for the full `message.created`.
- `tool.approval.executed` — tool result metadata

Drivers: `pusher` (pusher-js), `polling` (HTTP poll realtime endpoint), `socketio` (host relay — same event names).

## Message rendering

**Do not stream tokens by default.** Show Typing until `message.created` / `message.resumed`, then paint the complete bubble once.

Prefer `message.blocks` when `format === "blocks"`. Block types:
`text`, `html`, `table`, `list`, `card`, `code`, `actions` (buttons with label, action, style, payload).

Sanitize HTML blocks. Render `<img>` in attachment HTML. Wire `actions` / official Approve–Reject / in-chat handoff Yes–No to approval APIs.

Detect pending tool approval from tool error JSON: `{ "code": "pending_approval", "approval_id", "tool" }`.

After human handoff (`handoff: true` or inbox take), do not expect an agent job. Custom staff desk: `docs/STAFF_INBOX.md`. Env catalog: `docs/DEVELOPER_HANDBOOK.md` § Widget replies.

## Admin UI pages (React Router)

/, /inbox, /agents, /agents/:slug, /skills, /tools, /knowledge, /executions, /conversations, /widget-settings, /widget-settings/:agentSlug, /settings/locale

Load copy from GET `/translations`. Apply RTL layout when `meta.direction === "rtl"`.

## Widget UI components

- Config bootstrap, launcher, panel, intake wizard (if enabled), message list, block renderer, composer, conversation switcher, approval modal, realtime hook (pusher/poll/socket.io), guest id persistence.

## Tech constraints

- TypeScript, React 18+, modern fetch client with typed responses
- Do not mock APIs for final deliverable — use env-based base URL
- Accessible components (keyboard, ARIA on action buttons)

## Acceptance criteria

- Admin CRUD works against live API with pagination and ar/en locale switch
- Widget sends/receives messages, shows token counts, handles approval flow end-to-end
- Realtime: Typing until **full** `message.created` (do not stream tokens unless `config.stream` is true)
- Realtime updates messages when driver is pusher or polling
- Widget settings form validates against GET `/widget-settings/schema`

When unsure about a field, inspect Admin DTOs and controllers in the Agentic package source — do not invent routes.
```

---

## After implementation

When the Laravel package changes routes or events, update this prompt and `FRONTEND_IMPLEMENTATION_GUIDE.md` in the same commit.
