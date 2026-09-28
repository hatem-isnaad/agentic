# Channel accounts

A **channel account** is one messaging connection: a web widget, or one WhatsApp number. You can attach many. HTTP-tool OAuth stays in `agentic_connections` — do not mix the two.

Manage from **UI** (`/agentic/admin/channel-accounts`) or **CLI** (`php artisan agentic:make channel-account` — questions, summary, confirm). Same Admin API either way.

| Channel | Drivers you pick when connecting | Live now |
|---------|----------------------------------|----------|
| `widget` | `embed` | Same widget API (`wgt_…` + origin) |
| `whatsapp` | `meta_cloud` or `webjs` | **Meta Cloud is the live path.** `webjs` is an HTTP sidecar contract |
| `messenger` | `meta_cloud` | Presenter only — add a webhook when you have a Page |

## Connect a WhatsApp number

`POST /api/agentic/admin/channel-accounts`

**Meta Cloud (Business API)** — use this first:

```json
{
  "name": "Sales WhatsApp",
  "slug": "sales-wa",
  "channel": "whatsapp",
  "driver": "meta_cloud",
  "agent_slug": "support",
  "external_id": "PHONE_NUMBER_ID",
  "display_number": "+20100000000",
  "credentials": {
    "access_token": "EAAG…",
    "app_secret": "…",
    "verify_token": "your-verify-string"
  }
}
```

Webhook URL for Meta: `https://your-app.test/api/agentic/channels/whatsapp/meta`  
GET verify uses `hub.verify_token` (account or `AGENTIC_WHATSAPP_VERIFY_TOKEN`). POST must send `X-Hub-Signature-256`.

**webjs (link a device later)** — same table, other driver. Agentic does not run WhatsApp Web in PHP. A sidecar (whatsapp-web.js, or a hosted HTTP API such as WAHA / Evolution with the same shape) talks to WhatsApp. You store:

```json
{
  "channel": "whatsapp",
  "driver": "webjs",
  "external_id": "desk-1",
  "config": { "sidecar_url": "https://sidecar.example" },
  "credentials": { "sidecar_secret": "…" }
}
```

Sidecar contract:

- Inbound: `POST /api/agentic/channels/whatsapp/webjs` with `{ session, from, text }` and header `X-Agentic-Channel-Secret`
- Outbound: Agentic `POST {sidecar_url}/send` with `{ session, to, text }` and the same secret header

Multiple numbers = multiple rows. Unique is `(channel, driver, external_id)` — Meta `PHONE_NUMBER_ID` or webjs session name.

## Widget connection

Create a `widget` + `embed` row if you want the agent listed next to WhatsApp in admin. Chat still uses the existing embed token + origin lock. Rate limit: `AGENTIC_WIDGET_RATE_LIMIT_PER_MINUTE` (default 60, keyed by IP + token prefix + guest id).

## Scores

`POST /api/agentic/admin/evaluations` with `score` 1–5 (optional `conversation_id`, `execution_id`, `label`, `notes`). Ops quality, not required to install.

Env keys: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).
