# Developer quickstart (install → tools → KB → approvals)

In the admin SPA, open **Developer docs** (`/agentic/admin/docs`) for the full **24-chapter A→Z guide** (CLI, `.env`, admin API, PHP for every feature).

Goal: install the package, manage **your own PHP tools** without manual registry wiring, attach **knowledge (RAG)**, and run a **widget chatbot** with **human tool approval** when needed.

## 1. Install

```bash
composer require hatem-isnaad/agentic
php artisan agentic:install
php artisan vendor:publish --tag=ai-config
php artisan migrate
php artisan vendor:publish --tag=agentic-admin-assets --force   # when using the admin SPA
```

`laravel/ai` is installed automatically as a dependency of Agentic.

Copy AI / Agentic keys from `vendor/hatem-isnaad/agentic/.env.example` into your host `.env`.

## 2. Auto-managed code tools (recommended)

In `config/agentic.php`:

```php
'code_tools' => [
    'auto_register' => true,
    'paths' => [app_path('Agentic/Tools/Custom')],
    'namespace' => 'App\\Agentic\\Tools\\Custom',
],
```

Create a tool class:

```bash
php artisan agentic:make-code-tool CheckStock
```

Implement `DeclarativeCodeToolHandler` (metadata + `handle()`). On boot, classes under that path are **discovered and registered** automatically. Sync DB tool records:

```bash
php artisan agentic:code-tools-sync
```

In the admin UI: **Custom PHP tools** lists handlers; create/publish a **code** tool that points at the handler name.

## 3. Knowledge base (RAG)

1. Admin → **Knowledge** → create source (vector driver).
2. Paste text, URLs, or upload PDFs → **Ingest & reindex**.
3. Attach the source to a **Skill**, then attach the skill to an **Agent**.

Validate locally:

```bash
php artisan agentic:rag-validate --offline
php artisan agentic:rag-validate
```

## 4. Tool approval (human-in-the-loop)

In `.env`:

```env
AGENTIC_TOOL_APPROVAL_ENABLED=true
AGENTIC_TOOL_APPROVAL_PATTERNS=*.write,*.create,*.update,*.delete
```

Sensitive tools matching those patterns pause until approved (widget/admin approval APIs). Tune patterns per app.

## 5. Ship a chatbot

1. Create agent → set **Published**, pick provider/model, attach skills.
2. Optional: set **persona** (display name, gender, Arabic dialect, tone) on the agent form or `config.persona`.
3. Publish built assets (shipped in the package — no `npm` required on the host):

```bash
php artisan vendor:publish --tag=agentic-widget-assets --force
php artisan vendor:publish --tag=agentic-admin-assets --force
```

4. Create an embed token and lock origins (`php artisan agentic:widget-embed-token create` or Admin → Embed tokens).
5. Embed with `<x-agentic-widget agent="support" :token="env('AGENTIC_WIDGET_EMBED_TOKEN')" theme="isnaad" position="bottom-left" />` or `AgenticChat.init({…})`.
6. Demo page: `/agentic/widget?agent=your-slug`.

**Web replies are HTML.** The model writes Markdown; the widget renders bold, lists, tables, and code using the active theme. WhatsApp/Messenger use a different presenter later — do not send raw HTML to those channels.

Full embed guide: [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md).

## 6. Lock down admin (production)

**Default:** admin UI + admin API are open (local-friendly).

**When you need auth:**

```env
AGENTIC_ADMIN_REQUIRE_AUTH=true
AGENTIC_ADMIN_GATE=viewAgentic
```

In `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewAgentic', function ($user = null) {
    if (app()->environment('local')) {
        return true;
    }

    return $user !== null && in_array($user->email, [
        'ops@yourcompany.com',
    ], true);
});
```

For the **admin SPA** (session), add Laravel `auth` middleware to `config/agentic.php` → `admin.web.middleware`. For **API tokens**, `AGENTIC_ADMIN_REQUIRE_AUTH` adds `auth:sanctum` on the admin API.

Same gate also protects the **widget demo page** (`/agentic/widget`) when `AGENTIC_ADMIN_GATE` is set.

See [CONFIGURE_BY_CODE.md](./CONFIGURE_BY_CODE.md) for a full **UI → CLI → API → PHP** map, [SECURITY.md](./SECURITY.md), and [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md).
