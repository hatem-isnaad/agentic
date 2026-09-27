# Agentic implementation status

**Source of truth:** files under `routes/`, `src/`, and `database/migrations/` on `main`.  
**Last updated:** 2026-09-27

## Route surfaces (implemented)

| Prefix | File | Purpose |
|--------|------|---------|
| `/api/agentic` | `routes/api.php` | Runtime CRUD, execute, workflows, memories, knowledge ingest, MCP sync (`AGENTIC_API_ENABLED`) |
| `/api/agentic/admin` | `routes/admin-api.php` | Headless admin SPA (`AGENTIC_ADMIN_ENABLED`) |
| `/api/agentic/widget` | `routes/widget-api.php` | Embeddable widget (`AGENTIC_WIDGET_ENABLED`) |
| `/api/agentic/auth` | `routes/auth-api.php` | Sanctum + passkeys when packages installed (`AGENTIC_AUTH_ENABLED`) |

## Feature matrix

| Area | Status | Notes |
|------|--------|-------|
| Agent runtime + Laravel AI SDK | ✅ | Deferred tools, approvals, execution trace |
| Skills + routing (keyword + AI) | ✅ | Configurable |
| Tools HTTP / Code / MCP | ✅ | Connections, OAuth2, SSRF limits |
| Knowledge array + vector | ✅ | Chunking, ingest parsers, reindex |
| Memory (scoped) | ✅ | API + context injection |
| Workflows | ✅ | set / tool / agent / condition / complete |
| Admin API | ✅ | Agents, skills, tools, knowledge, executions, widget settings |
| Widget API | ✅ | Config, conversations, messages, approvals, realtime bridge |
| Auth API | ✅ | Sanctum + passkeys when host installs packages |
| MCP discovery | ✅ | `agentic:mcp-sync`, `POST .../mcp/servers/{server}/sync` |
| Filament UI | 🟡 | Config only; no bundled panel |
| Multi-tenant contract | ✅ | `TenantResolver`, request/conversation context providers |

## Knowledge ingest

`POST /api/agentic/knowledge-sources/{slug}/ingest` (and admin mirror) accepts:

- `format`: `text`, `markdown`, `html`, `json`
- `documents` or `raw_text`
- optional `urls` — HTTPS fetch (SSRF-safe, size-limited) merged before parsing
- optional `chunk_size`, `chunk_overlap`, `tenant`
- `reindex` (default `true`) — purges vector namespace then re-embeds

## MCP

1. Configure servers in host `config/mcp.php` (Laravel MCP).
2. Run `php artisan agentic:mcp-sync {server}` or call the runtime API sync endpoint.
3. Tools register in `ToolRegistry` with optional `AGENTIC_MCP_TOOL_PREFIX`.
