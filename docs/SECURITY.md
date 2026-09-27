# Agentic security notes

## Defaults (secure-by-default)

| Control | Default | Env |
|---------|---------|-----|
| Tool permissions | `deny` | `AGENTIC_PERMISSION_DEFAULT` |
| HTTP private/reserved hosts | blocked | `AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS` |
| HTTP redirects on tool/ingest fetch | disabled | `AGENTIC_HTTP_ALLOW_REDIRECTS` |
| Response size cap | 5 MB | `AGENTIC_HTTP_MAX_RESPONSE_BYTES` |
| Runtime API rate limit | 120/min per user/IP | `AGENTIC_API_RATE_LIMIT_*` |
| Tool approval heuristics | enabled | `AGENTIC_TOOL_APPROVAL_ENABLED` |
| Permission checker | deny-all class | `AGENTIC_PERMISSION_CHECKER` (use `RuleBasedPermissionChecker` + patterns in prod) |

## Host responsibilities

1. **Authentication** — Admin UI/API are **open by default** (local dev). For production enable `AGENTIC_API_REQUIRE_AUTH` / `AGENTIC_ADMIN_REQUIRE_AUTH`, add `auth` middleware to `admin.web.middleware` for the SPA, and optionally `AGENTIC_ADMIN_GATE` with `Gate::define()` (Telescope-style allow lists). See [DEVELOPER_QUICKSTART.md](./DEVELOPER_QUICKSTART.md).
2. **Tenant isolation** — Bind `Agentic\Contracts\TenantResolver` and pass tenant context on widget/runtime requests.
3. **Secrets** — Store connection credentials and OAuth client secrets outside version control; use Laravel encrypted env or vault.
4. **MCP** — Only register trusted MCP servers in `config/mcp.php`; sync exposes their tools to the agent runtime.

## Knowledge URL ingest

URL fetch uses the same SSRF rules as HTTP tools (`HttpUrlValidator`). Keep `allow_private_hosts` and `allow_unresolved_hosts` **false** in production unless you fully trust the ingest source list.
