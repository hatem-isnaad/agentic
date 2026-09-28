# Configure Agentic without the admin UI

Every `.env` key and artisan command: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).

**Two complete paths.** Use **only** `/agentic/admin` if you want a UI. Use **only** `php artisan agentic:make` / `agentic:manage` if you never want a UI. They write the same tables. You do not need both.

Interactive CLI: one question at a time, a summary table, then “Save this?”. Flags + `--no-interaction` skip the prompts.

Use this page when you prefer **commands**, **config**, **HTTP API**, or **PHP** over clicking in `/agentic/admin`.

Prefix defaults: **Admin API** `POST/GET /api/agentic/admin/...` · **Widget API** `/api/agentic/widget/...` · **Runtime API** `/api/agentic/...` (when `AGENTIC_API_ENABLED=true`).

## Install & environment

| UI | CLI / config |
|----|----------------|
| Package settings page | `php artisan agentic:install` · publish `config/agentic.php` · `.env` keys in `vendor/hatem-isnaad/agentic/.env.example` |
| AI provider / model | `AGENTIC_AI_PROVIDER`, `AGENTIC_AI_MODEL`, `config/agentic.php` → `ai.providers` |
| Vector / RAG | `AGENTIC_VECTOR_STORE`, `AGENTIC_KNOWLEDGE_*`, `php artisan agentic:rag-validate` |

## Code tools (PHP)

| UI | CLI | Admin API | PHP |
|----|-----|-----------|-----|
| Custom PHP tools | `php artisan agentic:make-code-tool Name` · `agentic:code-tools-sync` | `POST /tools` with `driver: code` | Classes in `app/Agentic/Tools/Custom` + `config/agentic.php` → `code_tools` (auto_register) |
| Handler registry | — | — | `HandlerRegistry::register()` or `DeclarativeCodeToolHandler` discovery |

## HTTP & MCP tools

| UI | CLI | Admin API |
|----|-----|-----------|
| HTTP connection (bearer / OAuth) | `php artisan agentic:make connection` | `POST /connections` · `POST /connections/{id}/refresh` |
| New HTTP tool | `php artisan agentic:make http-tool` | `POST /tools` |
| Test HTTP tool | `php artisan agentic:http-tool test {slug}` | `POST /tools/{slug}/test` |
| Clone tool | `php artisan agentic:http-tool clone {slug}` | `POST /tools/{slug}/clone` |
| HTTP tool test / clone | `php artisan agentic:http-tool test {slug}` | `POST /tools/{slug}/test` |
| MCP servers | `php artisan agentic:mcp-sync {server}` | `POST /mcp/servers/{server}/sync` |

## Skills & agents

| UI | CLI | Admin API | PHP |
|----|-----|-----------|-----|
| Skills | `php artisan agentic:make skill` | `POST /skills`, `PUT /skills/{slug}` | `SkillRepository::save()` |
| Agents | `php artisan agentic:make agent` | `POST /agents`, `PUT /agents/{slug}`, `POST /agents/{slug}/execute` | `AgentRepository::save()` |
| Agent voice (name, gender, language, dialect, tone) | `config.persona` on the agent payload | Same `config.persona` array (see below) |
| Attach skills/tools/KB | Include `skills`, `tools`, `knowledge` slugs on agent payload | Same arrays in repository save |

Persona is injected into the model instructions on every run. Presets live in `config/agentic.php` → `persona`.

```php
$agents->save([
    'name' => 'Support',
    'slug' => 'support',
    'instructions' => 'Help with orders and returns.',
    'status' => 'published',
    'config' => [
        'persona' => [
            'display_name' => 'Sara',      // Ahmed, Mohamed, Noura, …
            'gender' => 'female',          // male | female | unspecified
            'language' => 'ar',            // en | ar | bilingual
            'dialect' => 'saudi',          // msa | saudi | egyptian | gulf | levant
            'tone' => 'friendly',          // friendly | formal | casual | professional | warm
            'notes' => 'Keep replies short.',
        ],
    ],
]);
```

## Knowledge (RAG)

| UI | CLI | Admin API | PHP |
|----|-----|-----------|-----|
| Create source | `php artisan agentic:make knowledge` | `POST /knowledge-sources` | `KnowledgeRepository::save()` |
| Ingest text/PDF/URLs | `php artisan agentic:knowledge ingest` | `POST /knowledge-sources/{slug}/ingest` | `KnowledgeIngestor::ingest()` |
| Reindex | — | `POST /knowledge-sources/{slug}/index` | Via ingest with `reindex: true` |

## Channel accounts & evaluations

| UI | CLI | Admin API |
|----|-----|-----------|
| Channel accounts (widget / WhatsApp) | `php artisan agentic:make channel-account` | `POST /channel-accounts` |
| Evaluation scores 1–5 | `php artisan agentic:make evaluation` | `POST /evaluations` |

## Widget & chat

| UI | Config | Widget API |
|----|--------|------------|
| Widget settings per agent | `config/agentic.php` → `widget.*` · per-agent rows via admin or API | `GET /config?agent=` |
| Guest chat | `AGENTIC_WIDGET_*` (`STREAM` default false) | `POST /messages` + `X-Agentic-Guest-Id` → `{ pending }` then Pusher `message.created` |
| Resume conversation | — | `GET /conversations/{id}/messages` · `conversation_id` on `POST /messages` |

## Tool approvals

| UI | Config |
|----|--------|
| Approval patterns | `AGENTIC_TOOL_APPROVAL_ENABLED`, `AGENTIC_TOOL_APPROVAL_PATTERNS` in `.env` |
| Approve pending | Widget/admin approval endpoints (see package tests `WidgetApprovalApiTest`) |

## Memories

| UI | Admin API |
|----|-----------|
| Scoped memories | `GET/POST/DELETE /memories` with `scope`, `scope_key`, `key`, `content` |

## Workflows

| UI | CLI | Admin API |
|----|-----|-----------|
| Workflow definitions | — | `POST /workflows`, `POST /workflows/{slug}/execute` |
| Prune old runs | `php artisan agentic:prune-workflow-runs` | — |

## Admin security

| UI | Config / code |
|----|----------------|
| Open admin (laptop only) | `AGENTIC_ADMIN_REQUIRE_AUTH=false` (never on a live server) |
| Sanctum on admin API | Default. Keep `AGENTIC_ADMIN_REQUIRE_AUTH=true` |
| Telescope-style gate | `AGENTIC_ADMIN_GATE=viewAgentic` + `Gate::define('viewAgentic', fn ($user = null) => ...)` |
| SPA session auth | Auto `auth` outside local, or add `auth` to `admin.web.middleware` |

## Seed a full demo

```bash
php artisan db:seed --class=Agentic3plFulfillmentSeeder
```

Host example: `laravel-host/database/seeders/Agentic3plFulfillmentSeeder.php` (agents, skills, code tools, KB, memories).

Vendor-specific HTTP catalogs belong in the host app (a seeder + connection row). The package only ships the generic HTTP tool driver.

## JSON payloads

Use the in-app **JSON builder** (`/agentic/admin/docs/json-builder`) or copy bodies from `tests/Feature/*ApiTest.php`.
