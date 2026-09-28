# Agentic security notes

## Defaults (secure-by-default)

| Control | Default | Env |
|---------|---------|-----|
| Tool permissions | `deny` | `AGENTIC_PERMISSION_DEFAULT` |
| HTTP private/reserved hosts | blocked | `AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS` |
| HTTP redirects on tool/ingest fetch | disabled | `AGENTIC_HTTP_ALLOW_REDIRECTS` |
| Response size cap | 5 MB | `AGENTIC_HTTP_MAX_RESPONSE_BYTES` |
| Runtime API rate limit | 120/min per user/IP | `AGENTIC_API_RATE_LIMIT_*` |
| Widget API rate limit | 60/min per IP + token prefix + guest | `AGENTIC_WIDGET_RATE_LIMIT_*` |
| Channel webhooks | Meta HMAC + webjs secret header | `AGENTIC_CHANNELS_VERIFY_SIGNATURES` |
| Tool approval heuristics | enabled | `AGENTIC_TOOL_APPROVAL_ENABLED` |
| Permission checker | deny-all class | `AGENTIC_PERMISSION_CHECKER` (use `RuleBasedPermissionChecker` + patterns in prod) |
| Admin JSON | Sanctum | `AGENTIC_ADMIN_REQUIRE_AUTH` defaults **true** |
| Runtime API (workflows, MCP, memory) | Sanctum | `AGENTIC_API_REQUIRE_AUTH` defaults **true** |
| Widget user identity | `$request->user()` only | `X-Agentic-User-Id` is not identity |
| RAG query logs | off | `AGENTIC_RAG_LOG_QUERIES` / `AGENTIC_RAG_LOG_EMBEDDINGS` |

## Host responsibilities

1. **Authentication + authorization** — Admin JSON and `/api/agentic` (including workflow execute) require Sanctum unless you explicitly set the require-auth keys to `false`. Then `AuthorizeAgenticAdmin` runs the gate. Set `AGENTIC_ADMIN_GATE=viewAgentic` and `Gate::define()` or production admin/demo return 403. See [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) §4.
2. **Secrets** — Store connection credentials and OAuth client secrets outside version control; use Laravel encrypted env or vault.
3. **MCP** — Only register trusted MCP servers in `config/mcp.php`; sync exposes their tools to the agent runtime. Runtime MCP routes sit behind `AGENTIC_API_REQUIRE_AUTH`.

## Knowledge URL ingest

URL fetch uses the same SSRF rules as HTTP tools (`HttpUrlValidator`). Keep `allow_private_hosts` and `allow_unresolved_hosts` **false** in production unless you fully trust the ingest source list.
