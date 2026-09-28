# Agentic implementation status

**Source of truth:** files under `routes/`, `src/`, and `database/migrations/` on `main`.  
**Last updated:** 2026-09-28

## Route surfaces (implemented)

| Prefix | File | Purpose |
|--------|------|---------|
| `/api/agentic` | `routes/api.php` | Runtime CRUD, execute, workflows, memories, knowledge ingest, MCP sync (`AGENTIC_API_ENABLED`) |
| `/api/agentic/admin` | `routes/admin-api.php` | JSON admin API (`AGENTIC_ADMIN_API_ENABLED`) |
| `/{AGENTIC_ADMIN_PREFIX}` | `routes/admin-web.php` | Blade admin UI (`AGENTIC_ADMIN_WEB_ENABLED`) |
| `/api/agentic/widget` | `routes/widget-api.php` | Embeddable widget API (`AGENTIC_WIDGET_ENABLED`) |
| `/api/agentic/channels` | `routes/channels-api.php` | WhatsApp Meta + webjs webhooks |
| `/{AGENTIC_WIDGET_WEB_PREFIX}` | `routes/widget-web.php` | Standalone widget chat page (`AGENTIC_WIDGET_WEB_ENABLED`) |
| `/api/agentic/auth` | `routes/auth-api.php` | Sanctum + passkeys when packages installed (`AGENTIC_AUTH_ENABLED`) |

## Feature matrix

| Area | Status | Notes |
|------|--------|-------|
| Agent runtime + Laravel AI SDK | ✅ | OpenAI, Anthropic, **Gemini**, **Ollama** (SDK); provider registry in `config/agentic.php`; deferred tools, approvals, execution trace |
| Skills + routing (keyword + AI) | ✅ | Configurable |
| Tools HTTP / Code / MCP | ✅ | Connections, OAuth2, SSRF limits |
| Knowledge array + vector | ✅ | Laravel AI embeddings, pgvector JSON / native Postgres / Pinecone, `agentic:rag-validate`, ingest + search tests |
| Memory (scoped) | ✅ | API + context injection |
| Workflows | ✅ | set / tool / agent / condition / parallel / approval / complete; persisted runs (`workflow_run_id`); `GET .../workflow-runs` + `GET .../workflow-runs/{uuid}`; admin API parity; `POST .../resume` continues from saved step pointer |
| Admin API | ✅ | Agents, skills, tools, knowledge, workflows (CRUD + execute/resume), workflow runs, executions, widget settings |
| Widget API | ✅ | Config, conversations, messages, approvals, realtime; Form Requests + DTOs; `JsonApiResponse` |
| Widget embed SDK | ✅ | Published JS/CSS, `wgt_…` + origins, themes, inbox drawer, emoji, RTL |
| Channel replies | ✅ | Web/widget → Markdown HTML; WhatsApp/Messenger text presenters |
| Channel accounts | ✅ | Many connections: `widget`/`embed`, WhatsApp `meta_cloud` (live) + `webjs` (sidecar). Messenger kind reserved |
| WhatsApp live | ✅ | Meta Cloud webhook + Graph send; webjs HTTP contract for a later sidecar |
| Agent evals | ✅ | Admin `POST /evaluations` score 1–5 |
| Agent persona | ✅ | Name, gender, language/dialect, tone in `config.persona` + admin form |
| Lean context | ✅ | Default on; widget caps history/skills/tools/RAG per turn |
| Auth API | ✅ | Sanctum + passkeys when host installs packages |
| MCP discovery | ✅ | Tool sync, catalog API, optional resource injection via agent `config.mcp` |
| API rate limiting | ✅ | `throttle:agentic-api` on runtime; `throttle:agentic-widget` on widget |
| Filament UI (optional) | ✅ | Agents (+ skills), skills, tools, knowledge, workflows, workflow runs (read-only), executions (read-only) |
| Rule-based tool permissions | ✅ | `RuleBasedPermissionChecker` + allow/deny fnmatch patterns |

## Knowledge ingest

`POST /api/agentic/knowledge-sources/{slug}/ingest` (and admin mirror) accepts:

- `format`: `text`, `markdown`, `html`, `json`, `pdf` (requires `smalot/pdfparser`)
- `documents` or `raw_text`
- optional `urls` — HTTPS fetch (SSRF-safe, size-limited) merged before parsing
- optional `chunk_size`, `chunk_overlap`
- `reindex` (default `true`) — sync or queued (`AGENTIC_KNOWLEDGE_QUEUE_REINDEX`)

## MCP

1. Configure servers in host `config/mcp.php` (Laravel MCP).
2. Run `php artisan agentic:mcp-sync {server}` or call the runtime API sync endpoint.
3. Tools register in `ToolRegistry` with optional `AGENTIC_MCP_TOOL_PREFIX`.
4. Catalog (no sync): `GET .../tools`, `GET .../resources`, `POST .../resources/read`, `GET .../prompts`, `POST .../prompts/{name}`.

See [SECURITY.md](./SECURITY.md) for production hardening defaults and [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md) for host-app launch steps.
