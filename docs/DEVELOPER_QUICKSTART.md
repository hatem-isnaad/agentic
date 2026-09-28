# Developer quickstart (install → tools → KB → approvals)

**New here?** [START_HERE.md](./START_HERE.md) — words, and **fresh project vs attach to a finished app**. **Full env catalog:** [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).

In the admin SPA, open **Developer docs** (`/agentic/admin/docs`) for the in-app A→Z guide.

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

Copy the **starter** from `vendor/hatem-isnaad/agentic/.env.example`. Extra keys and token cost: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) only.

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

**Web replies are HTML.** WhatsApp replies are text only. Connect numbers in admin (`channel-accounts`) — Meta Cloud now; webjs sidecar later. See [CHANNELS.md](./CHANNELS.md).

Full embed guide: [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md).

## 6. Lock down admin (production)

Local and `testing` stay open when the require-auth keys are unset. Staging/production automatically attach `auth:sanctum` (admin API) and `auth` (admin views).

```env
AGENTIC_ADMIN_GATE=viewAgentic
```

In `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewAgentic', function ($user = null) {
    return $user !== null && in_array($user->email, [
        'ops@yourcompany.com',
    ], true);
});
```

Without a gate, production admin and `/agentic/widget` return 403. Full walkthrough: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) §4.

See [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md), [CONFIGURE_BY_CODE.md](./CONFIGURE_BY_CODE.md), [SECURITY.md](./SECURITY.md), and [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md).
