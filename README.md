# Agentic

**Agentic** is a Laravel-native, configuration-driven framework for building production AI agent systems on top of the [Laravel AI SDK](https://laravel.com/docs/ai-sdk). It provides orchestration, persistence, tools, skills, permissions, knowledge retrieval, execution tracking, and headless JSON APIs for separate **Admin** and **Widget** frontends.

| Layer | Responsibility |
|-------|----------------|
| **Your app** | Users, auth, business domain, React SPAs |
| **Agentic** | Agents, tools, skills, conversations, approvals, admin/widget APIs |
| **Laravel AI SDK** | Provider calls, streaming, native AI primitives |
| **LLM provider** | OpenAI, Anthropic, etc. |

```
Application (React admin + widget, domain services)
        ↓
Agentic (runtime, repositories, routing, knowledge, APIs)
        ↓
Laravel AI SDK
        ↓
LLM provider
```

**Design constraints:** The runtime never queries Eloquent directly; authorization is enforced before any tool driver runs; code tools never execute untrusted PHP from the UI.

---

## Table of contents

- [Requirements & install](#requirements--install)
- [Configuration overview](#configuration-overview)
- [Authentication (Sanctum + WebAuthn passkeys)](#authentication-sanctum--webauthn-passkeys)
- [Runtime quick start](#runtime-quick-start)
- [Tools (HTTP, code, MCP)](#tools-http-code-mcp)
- [Skills, routing & conversations](#skills-routing--conversations)
- [Knowledge & vector stores](#knowledge--vector-stores)
- [Memory, workflows & MCP](#memory-workflows--mcp)
- [Permissions & tool approval](#permissions--tool-approval)
- [Admin API (SPA dashboard)](#admin-api-spa-dashboard)
- [Widget API (embeddable chat)](#widget-api-embeddable-chat)
- [Optional runtime API](#optional-runtime-api)
- [Frontend documentation](#frontend-documentation)
- [Development & testing](#development--testing)

---

## Requirements & install

- PHP **8.3+**
- Laravel **12+** (package supports `illuminate/*` ^12|^13)
- [`laravel/ai`](https://github.com/laravel/ai) ^1.0

```bash
composer require hatem-isnaad/agentic
php artisan vendor:publish --tag=agentic-config
php artisan migrate
```

Copy variables from [`.env.example`](.env.example) into your application `.env`. The package ships **no production UI** (Filament removed); you consume JSON APIs from your own React apps.

---

## Configuration overview

Published config: `config/agentic.php`. Common environment flags:

```env
# Core
AGENTIC_ENABLED=true
AGENTIC_AI_PROVIDER=openai
AGENTIC_AI_MODEL=gpt-4.1-mini
AGENTIC_PERMISSION_DEFAULT=deny

# Headless admin JSON (React dashboard)
AGENTIC_ADMIN_ENABLED=true
AGENTIC_ADMIN_API_PREFIX=api/agentic/admin
AGENTIC_ADMIN_REQUIRE_AUTH=false

# Embeddable widget JSON (React widget)
AGENTIC_WIDGET_ENABLED=true
AGENTIC_WIDGET_PREFIX=api/agentic/widget
AGENTIC_WIDGET_AUTH_MODE=both

# Auth (Sanctum + passkeys)
AGENTIC_AUTH_ENABLED=true
AGENTIC_AUTH_PREFIX=api/agentic/auth

# Optional programmatic CRUD/execute API
AGENTIC_API_ENABLED=false
```

Persistence drivers (`eloquent` vs `memory`) are configurable per subsystem — see `.env.example` for execution, conversation, and knowledge drivers.

---

## Authentication (Sanctum + WebAuthn passkeys)

Agentic registers a dedicated auth API (default **`/api/agentic/auth`**) using **[Laravel Sanctum](https://laravel.com/docs/sanctum)** and **[laravel/passkeys](https://github.com/laravel/passkeys-server)** (WebAuthn). Passkey routes replace the package defaults under your prefix; login returns JSON (and optionally a Bearer token).

### 1. Host application setup

```bash
composer require laravel/sanctum laravel/passkeys
php artisan vendor:publish --tag=sanctum-migrations
php artisan vendor:publish --tag=passkeys-migrations
php artisan migrate
```

Optional: publish `passkeys` config and set `management_middleware` to `[]` if you rely on passkeys instead of `password.confirm` for registering devices.

### 2. User model

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements PasskeyUser
{
    use HasApiTokens;
    use PasskeyAuthenticatable;

    protected $fillable = ['name', 'email', 'password'];
}
```

### 3. Sanctum SPA domains

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,your-app.test
SESSION_DOMAIN=.your-app.test
```

Configure CORS in your Laravel app so your React origin may send cookies and `X-XSRF-TOKEN`.

### 4. Protect admin (and runtime) APIs

```env
AGENTIC_ADMIN_REQUIRE_AUTH=true
AGENTIC_API_REQUIRE_AUTH=true
AGENTIC_AUTH_ISSUE_TOKEN_ON_PASSKEY_LOGIN=true
AGENTIC_AUTH_TOKEN_NAME=agentic-admin
```

When enabled, admin routes use `auth:sanctum` plus Sanctum’s stateful middleware for cookie-based SPAs.

### 5. Auth API routes

| Method | Path | Access | Description |
|--------|------|--------|-------------|
| `GET` | `/passkeys/login/options` | guest | WebAuthn assertion options |
| `POST` | `/passkeys/login` | guest | Verify passkey; session + optional token |
| `GET` | `/me` | sanctum | Current user |
| `POST` | `/token` | sanctum | Issue personal access token |
| `POST` | `/logout` | sanctum | Revoke token and session |
| `GET` | `/passkeys/register/options` | sanctum | Register new passkey |
| `POST` | `/passkeys/register` | sanctum | Store passkey |
| `DELETE` | `/passkeys/{id}` | sanctum | Remove passkey |

**Successful passkey login (example response):**

```json
{
  "data": {
    "user": { "id": 1, "name": "Ops", "email": "ops@example.com" },
    "token": "1|plainTextTokenOnlyShownOnce..."
  }
}
```

### 6. Example: SPA login flow (JavaScript)

Use the official client for ceremonies; Agentic handles server routes.

```bash
npm install @laravel/passkeys
```

```javascript
import { Passkeys } from '@laravel/passkeys'

const base = import.meta.env.VITE_API_BASE_URL
const authPrefix = '/api/agentic/auth'

// 1) Sanctum CSRF cookie (host app route)
await fetch(`${base}/sanctum/csrf-cookie`, { credentials: 'include' })

// 2) Passwordless login
await Passkeys.verify({
  routes: {
    options: `${base}${authPrefix}/passkeys/login/options`,
    verify: `${base}${authPrefix}/passkeys/login`,
  },
  credentials: 'include',
})

// 3) Optional: mint a long-lived Bearer token for mobile/CLI
const tokenRes = await fetch(`${base}${authPrefix}/token`, {
  method: 'POST',
  credentials: 'include',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  body: JSON.stringify({ token_name: 'admin-spa' }),
})
const { data } = await tokenRes.json()
// Authorization: Bearer ${data.token}
```

### 7. Example: Admin request with Bearer token

```bash
curl -s -H "Authorization: Bearer 1|your-token" \
  -H "X-Agentic-Locale: en" \
  https://your-app.test/api/agentic/admin/dashboard | jq .
```

### 8. Register a passkey (authenticated)

```javascript
await Passkeys.register({
  name: 'MacBook Pro',
  routes: {
    options: `${base}${authPrefix}/passkeys/register/options`,
    store: `${base}${authPrefix}/passkeys/register`,
  },
  credentials: 'include',
})
```

---

## Runtime quick start

Define agents in code or persist them via the Admin API, then run through `AgentRuntime`:

```php
use Agentic\Agent\AgentDefinition;
use Agentic\Agent\ConfigurableAgent;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Runtime\AgentRuntime;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;

app(SkillRegistry::class)->register(new SkillDefinition(
    name: 'orders',
    description: 'Order lookup and updates',
    tools: ['orders.search', 'orders.get'],
));

$agent = new ConfigurableAgent(
    new AgentDefinition(
        name: 'Support',
        slug: 'support',
        instructions: 'You help customers with orders. Be concise.',
        skills: ['orders'],
        provider: 'openai',
        model: 'gpt-4.1-mini',
    ),
    app(AgentRuntime::class),
);

$output = $agent->run(new AgentExecutionContext(
    message: 'Where is order #1042?',
    metadata: ['channel' => 'widget'],
));

// $output->content(), tool steps in execution repository, etc.
```

Resolve **database-backed** agents by slug:

```php
use Agentic\Agent\AgentResolver;

$definition = app(AgentResolver::class)->resolve('support');
```

---

## Tools (HTTP, code, MCP)

### HTTP tool (REST integration)

```php
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolFactory;

app(ToolFactory::class)->register(new ToolDefinition(
    name: 'orders.get',
    description: 'Fetch order by ID',
    inputSchema: [
        'id' => ['type' => 'integer', 'required' => true],
    ],
    driver: 'http',
    configuration: [
        'method' => 'GET',
        'url' => 'https://api.example.com/orders/{id}',
        'auth' => ['type' => 'bearer', 'token' => 'env:ORDERS_API_TOKEN'],
        'timeout' => 15,
        'retry' => ['times' => 2, 'sleep' => 200],
        'response_mapping' => [
            'order_id' => 'body.id',
            'status' => 'body.fulfillment_status',
        ],
    ],
));

$result = app(\Agentic\Tool\Registry\ToolRegistry::class)
    ->resolve('orders.get')
    ->execute(new ToolExecutionContext(arguments: ['id' => 1042]));
```

### Code tool (trusted PHP handler)

Handlers are registered in a service provider — never from admin UI input.

```php
use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolFactory;
use Agentic\Tool\ToolResult;

app(HandlerRegistry::class)->register('orders.search', new class implements CodeToolHandler {
    public function handle(ToolExecutionContext $context): ToolResult
    {
        $query = (string) ($context->arguments['q'] ?? '');
        // Query your domain layer / Eloquent here
        return ToolResult::success(['orders' => [], 'query' => $query]);
    }
});

app(ToolFactory::class)->register(new ToolDefinition(
    name: 'orders.search',
    description: 'Search orders by email or reference',
    driver: 'code',
    configuration: ['handler' => 'orders.search'],
));
```

### MCP tools

```php
use Agentic\Tool\Drivers\Mcp\McpToolRegistrar;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;

Mcp::registerClient('warehouse', fn () => Client::web('https://mcp.example.com'));

app(McpToolRegistrar::class)->registerServer('warehouse', 'mcp.warehouse.');
```

### Custom driver

```php
use Agentic\Tool\DriverResolver;

app(DriverResolver::class)->extend('billing', App\Agentic\BillingToolDriver::class);
```

Built-in drivers: `http`, `code`, `mcp`.

---

## Skills, routing & conversations

**Skills** group tools for an agent:

```php
app(\Agentic\Skill\SkillRegistry::class)->register(new \Agentic\Skill\SkillDefinition(
    name: 'billing',
    description: 'Invoices and refunds',
    tools: ['invoices.list', 'refunds.create'],
));
```

**Multi-agent routing** (keyword + slug strategies):

```php
use Agentic\Routing\AgentRouter;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\Strategies\KeywordRoutingStrategy;
use Agentic\Routing\Strategies\SlugRoutingStrategy;

$result = app(AgentRouter::class)
    ->use(new SlugRoutingStrategy())
    ->use(new KeywordRoutingStrategy([
        'support' => ['refund', 'broken', 'help'],
        'sales' => ['pricing', 'demo', 'enterprise'],
    ]))
    ->route(new RoutingContext(message: $request->string('message')));

// $result->agent → slug passed to AgentResolver
```

**Conversations** (persisted metadata; AI SDK handles provider history):

```php
use Agentic\Conversation\ConversationManager;
use Agentic\Execution\AgentExecutionContext;

$conversation = app(ConversationManager::class)->continueOrStart(
    agentSlug: 'support',
    userId: auth()->id(),
);

app(AgentRuntime::class)->run($agent, new AgentExecutionContext(
    message: 'Follow up on my ticket',
    conversation: $conversation,
));
```

**Context** injected into runs:

```php
use Agentic\Context\ContextManager;
use Agentic\Context\Providers\ArrayContextProvider;

app(ContextManager::class)->extend(new ArrayContextProvider([
    'locale' => 'ar',
    'store_id' => 'DXB-01',
]));
```

---

## Knowledge & vector stores

```env
AGENTIC_KNOWLEDGE_DRIVER=eloquent
AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai
AGENTIC_KNOWLEDGE_EMBEDDING_MODEL=text-embedding-3-small
AGENTIC_VECTOR_STORE=array          # array | pgvector | pinecone
PINECONE_HOST=https://index.svc.pinecone.io
PINECONE_API_KEY=...
```

**Admin API — index and search** (after creating a knowledge source):

```bash
# Re-index documents for source slug "product-docs"
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  https://your-app.test/api/agentic/admin/knowledge-sources/product-docs/index

# Semantic search
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"query":"return policy for electronics","limit":5}' \
  https://your-app.test/api/agentic/admin/knowledge-sources/product-docs/search | jq .
```

`pgvector` stores vectors in `agentic_vector_entries`; `pinecone` uses the hosted index API.

**Ingest with parsers** (`text`, `markdown`, `html`, `json`) — parses content, updates the source, then re-indexes:

```bash
curl -s -X POST -H "Content-Type: application/json" \
  -d '{"format":"markdown","raw_text":"# Returns\n\nWithin 30 days."}' \
  https://your-app.test/api/agentic/knowledge-sources/product-docs/ingest
```

See [docs/IMPLEMENTATION_STATUS.md](docs/IMPLEMENTATION_STATUS.md) for the full route matrix.

---

## Memory, workflows & MCP

- **Memory** — `POST/GET /api/agentic/memories` (scoped facts injected into agent context).
- **Workflows** — `POST /api/agentic/workflows/{slug}/execute` with steps: `set`, `tool`, `agent`, `condition`, `complete`.
- **MCP** — configure `config/mcp.php` in the host app, then `php artisan agentic:mcp-sync {server}` or `POST /api/agentic/mcp/servers/{server}/sync`.

---

## Permissions & tool approval

Default permission mode is **deny** — tools must be explicitly allowed.

```php
// config/agentic.php
'permissions' => [
    'default' => env('AGENTIC_PERMISSION_DEFAULT', 'deny'),
    'gates' => [
        'tool:orders.read' => 'auth',
        'tool:orders.write' => 'auth',
    ],
    'rules' => [
        ['pattern' => 'tool:public.*', 'effect' => 'allow'],
    ],
],
```

**Human-in-the-loop** for destructive tools (widget + runtime):

```env
AGENTIC_TOOL_APPROVAL_ENABLED=true
AGENTIC_TOOL_APPROVAL_PATTERNS=*.write,*.create,*.update,*.delete
AGENTIC_TOOL_APPROVAL_AUTO_EXECUTE=true
AGENTIC_TOOL_APPROVAL_AUTO_RESUME=true
```

When the model attempts a gated write, the widget receives a structured error:

```json
{
  "code": "pending_approval",
  "approval_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool": "orders.update"
}
```

Approve from the widget API:

```bash
curl -s -X POST \
  -H "X-Agentic-Guest-Id: guest-abc-123" \
  https://your-app.test/api/agentic/widget/approvals/550e8400-e29b-41d4-a716-446655440000/approve
```

With auto-resume enabled, the response may include a new assistant `message` after the tool runs.

---

## Admin API (SPA dashboard)

Base URL: **`/api/agentic/admin`** (prefix configurable). Uses middleware `api` + locale; add Sanctum when `AGENTIC_ADMIN_REQUIRE_AUTH=true`.

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/dashboard` | Stats + RTL/LTR meta |
| `GET` | `/translations` | Full i18n tree (`en`, `ar`) |
| `GET`/`PUT` | `/locale` | `{ "locale": "ar" }` |
| CRUD | `/agents`, `/skills`, `/tools`, `/knowledge-sources` | Paginated (`page`, `per_page`, `q`, `status`) |
| `POST` | `/agents/{slug}/execute` | `{ "message", "conversation_id?", "metadata?" }` |
| `POST` | `/knowledge-sources/{slug}/index` | Trigger indexer |
| `POST` | `/knowledge-sources/{slug}/search` | `{ "query", "limit?" }` |
| `GET` | `/widget-settings/schema` | JSON schema for widget overrides |
| `PUT` | `/widget-settings/{agentSlug}` | Per-agent widget theme/intake/auth |
| `GET` | `/executions`, `/conversations` | Read-only, filterable |

**Create agent example:**

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Agentic-Locale: en" \
  -d '{
    "name": "Support",
    "slug": "support",
    "description": "Customer support agent",
    "status": "published",
    "instructions": "Help with orders and returns.",
    "provider": "openai",
    "model": "gpt-4.1-mini",
    "skills": ["orders"]
  }' \
  https://your-app.test/api/agentic/admin/agents
```

**Execute agent from admin (smoke test):**

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message":"Summarize open refunds policy"}' \
  https://your-app.test/api/agentic/admin/agents/support/execute | jq .
```

List responses include pagination meta: `total`, `page`, `per_page`, `locale`, `direction`, `is_rtl`.

Optional Blade admin placeholders (disabled by default): `AGENTIC_ADMIN_WEB_ENABLED=true`.

---

## Widget API (embeddable chat)

Base URL: **`/api/agentic/widget`**. Supports **guest** and **authenticated** users (`AGENTIC_WIDGET_AUTH_MODE`).

| Endpoint | Purpose |
|----------|---------|
| `GET /config?agent={slug}` | Theme, intake, locales, realtime driver, AI provider registry |
| `GET/POST /conversations` | List/create (`agent` query/body required) |
| `POST /messages` | Send message; optional `conversation_id` |
| `GET /conversations/{id}/messages` | History with `html`, `blocks`, token counts |
| `GET /conversations/{id}/realtime` | Long-poll when driver=`polling` |
| `POST /approvals/{id}/approve\|reject\|execute` | Tool approval workflow |

**Guest message example:**

```bash
curl -s -X POST \
  -H "Content-Type: application/json" \
  -H "X-Agentic-Guest-Id: guest-$(uuidgen)" \
  -H "X-Agentic-Locale: en" \
  -d '{"agent":"support","message":"Hi, I need help with order 1042"}' \
  https://your-app.test/api/agentic/widget/messages | jq .
```

**Structured assistant reply (blocks):**

```json
{
  "format": "blocks",
  "blocks": [
    { "type": "text", "text": "I can update the shipping address. Confirm?" },
    {
      "type": "actions",
      "buttons": [
        {
          "label": "Approve",
          "action": "approve",
          "style": "primary",
          "payload": { "id": "approval-uuid" }
        }
      ]
    }
  ]
}
```

**Realtime** (`AGENTIC_WIDGET_BROADCAST_DRIVER`):

| Driver | Client behavior |
|--------|-----------------|
| `pusher` | Subscribe to `{prefix}.{conversationId}` |
| `polling` | `GET .../realtime?since_id=` on an interval |
| `socketio` | Your relay receives HTTP events from Laravel |
| `null` | HTTP-only |

Events: `message.created`, `message.resumed`, `tool.approval.executed`.

---

## Optional runtime API

Enable CRUD + execute under **`/api/agentic`** when you want a programmatic integration surface separate from the admin namespace:

```env
AGENTIC_API_ENABLED=true
AGENTIC_API_PREFIX=api/agentic
AGENTIC_API_REQUIRE_AUTH=true
```

Prefer the **admin API** for dashboard operations; use the runtime API for service-to-service automation.

---

## Frontend documentation

The package does not ship production React UI. Implement two apps against the JSON APIs:

| Document | Purpose |
|----------|---------|
| [docs/FRONTEND_IMPLEMENTATION_GUIDE.md](docs/FRONTEND_IMPLEMENTATION_GUIDE.md) | **Source of truth** — every route, header, event, block type |
| [docs/COPY_PROMPT_FOR_AI.md](docs/COPY_PROMPT_FOR_AI.md) | Paste into Cursor/Claude to scaffold Admin + Widget |
| [docs/SYSTEM_DESIGN.md](docs/SYSTEM_DESIGN.md) | Sequence diagrams (approval, realtime, auth) |
| [.env.example](.env.example) | Backend environment reference |

---

## Development & testing

```bash
composer install
./vendor/bin/phpunit
```

Package tests use Orchestra Testbench with in-memory drivers by default. Auth tests cover Sanctum session routes, passkey login options, and optional admin API protection.

---

## Design rules

1. **Runtime** must not query Eloquent — use repositories and resolvers.
2. **LLM output is not authorization** — `PermissionChecker` runs before tool drivers.
3. **Code tools** execute only registered handlers, never user-supplied PHP.
4. **Do not duplicate Laravel AI SDK** — providers, streaming, and MCP wire through the SDK and `laravel/mcp`.

---

## License

MIT. See [composer.json](composer.json).
