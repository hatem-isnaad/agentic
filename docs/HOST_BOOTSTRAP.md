# Host application bootstrap (complete)

Use this once when embedding Agentic in a **new or existing Laravel 12** app. The package repo is complete; this is the standard host wiring.

## 1. Require the package

**From Packagist / VCS:**

```bash
composer require hatem-isnaad/agentic laravel/ai
```

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
    "hatem-isnaad/agentic": "@dev",
    "laravel/ai": "^1.0"
}
```

## 2. Install & migrate

```bash
php artisan agentic:install
php artisan vendor:publish --tag=ai-config
php artisan migrate
```

Optional host packages:

```bash
composer require laravel/sanctum smalot/pdfparser
# composer require laravel/passkeys filament/filament
```

## 3. Environment (minimum viable local)

Merge from `vendor/hatem-isnaad/agentic/.env.example`:

```env
AGENTIC_ENABLED=true
AGENTIC_AI_PROVIDER=ollama
AGENTIC_AI_MODEL=qwen3.5:4b
OLLAMA_URL=http://localhost:11434

AGENTIC_KNOWLEDGE_EMBEDDING=laravel_ai
AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER=ollama
AGENTIC_KNOWLEDGE_EMBEDDING_MODEL=nomic-embed-text
AGENTIC_VECTOR_STORE=pgvector
AGENTIC_PGVECTOR_DIMENSIONS=768

AGENTIC_ADMIN_ENABLED=true
AGENTIC_WIDGET_ENABLED=true
AGENTIC_API_ENABLED=true
```

Cloud alternative: set `AGENTIC_AI_PROVIDER=gemini` or `openai` and the matching `*_API_KEY`.

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
- Sanctum on `User` model
- `AGENTIC_PERMISSION_DEFAULT=deny`
- Queue worker if `AGENTIC_KNOWLEDGE_QUEUE_REINDEX=true`

Schedule workflow run cleanup in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('agentic:prune-workflow-runs')->daily();
```

## 7. Frontends (host-owned)

| App | API base | Doc |
|-----|----------|-----|
| Admin React SPA | `/api/agentic/admin` | [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) |
| Widget React SPA | `/api/agentic/widget` | Same |

Copy prompt for AI builders: [COPY_PROMPT_FOR_AI.md](./COPY_PROMPT_FOR_AI.md)

## 8. Package vs host responsibilities

| Done in package | Done in host |
|-----------------|--------------|
| Runtime, APIs, RAG, workflows, tests | Users, domain, UI SPAs |
| `agentic:install`, `agentic:rag-validate`, `agentic:prune-workflow-runs` | E2E tests, deploy, secrets |
| Optional Filament ops UI | Production DB, queues, Pusher |

When steps 1–6 pass, the **backend** is ready; step 7 is the product UI.
