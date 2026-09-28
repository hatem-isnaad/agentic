# Host application bootstrap (complete)

Use this once when embedding Agentic in a **new or existing Laravel 12** app.

- **Fresh app** (empty Laravel): create the project, then follow the steps below.
- **Finished app** (already has users, routes, UI): run the same steps **inside that project**. Append starter `.env` keys; do not replace your app. Admin uses your login + a gate. The widget goes on a page you already have.

Which path and what stays yours: [START_HERE.md](./START_HERE.md) → **Two situations**. **Every extra `.env` key:** [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md). Fast path: [DEVELOPER_QUICKSTART.md](./DEVELOPER_QUICKSTART.md).

## 1. Require the package

**From Packagist / VCS:**

```bash
composer require hatem-isnaad/agentic
```

`laravel/ai` is pulled in automatically (see `composer.json` → `require`).

**Local path (monorepo):** use the reference app at `laravel-host/` (sibling of `agentic/`):

```bash
cd laravel-host
composer install
php artisan migrate
php artisan db:seed
php artisan serve
```

Or wire your own app with:

```json
"repositories": [
    { "type": "path", "url": "../agentic", "options": { "symlink": true } }
],
"require": {
    "hatem-isnaad/agentic": "@dev"
}
```

## 2. Install & migrate

```bash
php artisan agentic:install
```

This runs the **interactive wizard** (choices + credentials → `.env`). See [INSTALL_WIZARD.md](./INSTALL_WIZARD.md). Use `--quick` to skip.

The wizard can run `migrate` and publish assets when you confirm at the end. Otherwise:

```bash
php artisan vendor:publish --tag=ai-config
php artisan migrate
```

Optional host packages:

```bash
composer require laravel/sanctum smalot/pdfparser
# composer require laravel/passkeys filament/filament
```

## 3. Environment

Copy the **starter** from `vendor/hatem-isnaad/agentic/.env.example`. That is the only list you need on day one.

Any extra key, small vs large, tokens: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).

## 4. Validate stack

```bash
php artisan agentic:rag-validate --offline
php artisan agentic:rag-validate
```

## 5. Create first agent (admin API)

```bash
curl -s -X POST http://localhost/api/agentic/admin/agents \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Support",
    "slug": "support",
    "status": "published",
    "instructions": "You are a helpful support agent.",
    "provider": "ollama",
    "model": "qwen3.5:4b"
  }'
```

Widget config: `GET /api/agentic/widget/config?agent=support`

## 6. Production hardening

Follow [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md):

- `AGENTIC_ADMIN_REQUIRE_AUTH=true` + `AGENTIC_API_REQUIRE_AUTH=true`
- Optional `AGENTIC_ADMIN_GATE=viewAgentic` + `Gate::define('viewAgentic', ...)` in `AppServiceProvider`
- Sanctum on `User` model
- `AGENTIC_PERMISSION_DEFAULT=deny`
- Queue worker if `AGENTIC_KNOWLEDGE_QUEUE_REINDEX=true`

Schedule workflow run cleanup in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('agentic:prune-workflow-runs')->daily();
```

## 7. UI

| Surface | URL (defaults) | Notes |
|---------|----------------|-------|
| Blade admin | `/agentic/admin` | `AGENTIC_ADMIN_WEB_ENABLED=true` |
| Widget chat page | `/agentic/widget` | `AGENTIC_WIDGET_WEB_ENABLED=true`. Pusher + `queue:work`. Stream off: full `message.created` — [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) § Widget replies |
| Admin API (SPA) | `/api/agentic/admin` | [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) |
| Widget API (embed) | `/api/agentic/widget` | Same guide §5–6 (`pending` + `message.created`, ignore `message.delta`) |

Copy prompt for custom React SPAs: [COPY_PROMPT_FOR_AI.md](./COPY_PROMPT_FOR_AI.md)

### Monorepo: keep `laravel-host` in sync

After every package change, from `laravel-host/` run:

```bash
composer agentic-sync
```

Refresh `.env` from new keys in package `.env.example`, and extend host smoke tests when new routes ship.

## 8. Package vs host responsibilities

| Done in package | Done in host |
|-----------------|--------------|
| Runtime, APIs, RAG, workflows, tests | Users, domain, UI SPAs |
| `agentic:install`, `agentic:rag-validate`, `agentic:prune-workflow-runs` | E2E tests, deploy, secrets |
| Optional Filament ops UI | Production DB, queues, Pusher |

When steps 1–6 pass, the **backend** is ready; step 7 is the product UI.
