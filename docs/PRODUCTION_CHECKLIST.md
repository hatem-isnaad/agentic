# Production checklist

Use this when embedding Agentic in a real Laravel application. The package provides orchestration and APIs; **your host app** owns auth, UI, and domain policy.

## 1. Install and migrate

```bash
composer require hatem-isnaad/agentic
php artisan vendor:publish --tag=agentic-config
php artisan migrate
```

Optional host packages:

| Package | Purpose |
|---------|---------|
| `laravel/sanctum` | Admin/runtime API tokens and widget auth |
| `laravel/passkeys` | WebAuthn routes under `/api/agentic/auth` |
| `smalot/pdfparser` | PDF knowledge ingest (`format=pdf`) |
| `filament/filament` | Optional ops UI via `AgenticPlugin` |

## 2. AI and permissions

```env
AGENTIC_AI_PROVIDER=openai
AGENTIC_AI_MODEL=gpt-4.1-mini
AGENTIC_PERMISSION_DEFAULT=deny
AGENTIC_PERMISSION_CHECKER=Agentic\Permission\RuleBasedPermissionChecker
AGENTIC_PERMISSION_ALLOW_PATTERNS=orders.*,crm.*
AGENTIC_PERMISSION_DENY_PATTERNS=*.delete,secrets.*
```

- **DenyAll** (default checker) blocks every tool until agent/skill allow-lists or Laravel `Gate` grants access.
- **RuleBasedPermissionChecker** — pattern allow/deny lists (see above).
- Implement a custom `PermissionChecker` for RBAC tied to your users/roles.

## 3. Secure APIs

```env
AGENTIC_ADMIN_REQUIRE_AUTH=true
AGENTIC_API_REQUIRE_AUTH=true
```

Install and configure Sanctum (or your guard). Enable `AGENTIC_API_RATE_LIMIT_ENABLED=true` in production.

Workflow approvals: use `workflow_run_id` from execute/resume responses and `GET /api/agentic/workflow-runs/{uuid}` to inspect pending state before calling resume.

Keep SSRF protections strict:

```env
AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS=false
AGENTIC_HTTP_ALLOW_UNRESOLVED_HOSTS=false
AGENTIC_HTTP_ALLOW_REDIRECTS=false
```

See [SECURITY.md](./SECURITY.md).

## 4. Knowledge (RAG)

```env
AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai
AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER=openai
AGENTIC_KNOWLEDGE_EMBEDDING_MODEL=text-embedding-3-small
AGENTIC_VECTOR_STORE=pgvector
# or AGENTIC_VECTOR_STORE=pinecone + PINECONE_HOST / PINECONE_API_KEY
```

Run ingest + reindex via admin/runtime API or Filament. For large corpora:

```env
AGENTIC_KNOWLEDGE_QUEUE_REINDEX=true
```

Run `php artisan queue:work` in the host app.

## 5. Multi-tenant

Bind `Agentic\Contracts\TenantResolver` if defaults are not enough. Pass tenant on widget (`X-Agentic-Tenant-Id`) and ensure knowledge ingest sets `tenant` metadata for vector isolation.

## 6. MCP

1. Configure `config/mcp.php` (Laravel MCP).
2. `php artisan agentic:mcp-sync {server}` or `POST /api/agentic/mcp/servers/{server}/sync`.
3. Treat synced tools as **trusted code** — same permission and approval rules as native tools.
4. Optional: add to agent `config` JSON — `"mcp": { "server": "docs", "resource_uris": ["file:///policy.md"] }` for runtime knowledge injection.
5. Resources/prompts catalog: runtime API under `/mcp/servers/{server}/...`.

## 7. Frontends (required for end users)

The package does **not** ship production React apps.

| Surface | API prefix | Doc |
|---------|------------|-----|
| Admin SPA | `/api/agentic/admin` | [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) |
| Widget SPA | `/api/agentic/widget` | Same guide (messages, approvals, realtime) |

Configure Pusher or polling for widget realtime (`AGENTIC_WIDGET_BROADCAST_DRIVER`).

## 8. Optional Filament ops UI

```env
AGENTIC_FILAMENT_PANELS=admin
```

In your `PanelProvider`:

```php
->plugin(\Agentic\Filament\AgenticPlugin::make())
```

Or rely on auto-registration when the panel ID is listed in config. Filament covers basic CRUD — not full agent↔skill relation editing or execution dashboards.

## 9. Workflows

- Approvals pause with **HTTP 202**; approve via widget/admin, then `POST .../workflows/{slug}/resume` with `approval_id` (or re-execute with `input._resume_approval_id`).
- Parallel branches run sequentially in PHP (isolated variables, merged results).

## 10. Verify before launch

- [ ] `./vendor/bin/phpunit` in CI (package) + your app test suite
- [ ] Tool deny/allow matches product policy (test a forbidden tool call)
- [ ] Widget approval flow end-to-end
- [ ] RAG retrieval returns tenant-scoped chunks only
- [ ] OAuth2/HTTP tools only reach allowed hosts
- [ ] Secrets in `agentic_connections` / env, not in git

## Related docs

- [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md) — feature matrix
- [SYSTEM_DESIGN.md](./SYSTEM_DESIGN.md) — architecture
- [SECURITY.md](./SECURITY.md) — defaults and host responsibilities
