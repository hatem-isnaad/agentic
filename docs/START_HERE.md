# Start here

You do not need to know the whole package. Read this page first. Then open only the doc for the job you have.

**Agentic** is a Laravel package that adds **AI chat** to your app: a staff **admin** screen, and a **chat widget** you put on a website.

It does not call OpenAI or Claude by itself. Your Laravel app already has keys. Agentic uses the [Laravel AI SDK](https://laravel.com/docs/ai-sdk) to talk to the model you choose.

---

## Words we use

| Word | Meaning in this package |
|------|-------------------------|
| **Agent** | One chatbot. It has a name, instructions, and a voice (persona). Example: `support`. |
| **Tool** | One action the agent may run. Example: look up an order, call an HTTP API, run your PHP class. |
| **Skill** | A bundle of tools (and maybe documents) for one topic. Example: “orders”. |
| **Knowledge** | Documents the agent can search (policies, FAQs). Also called RAG. |
| **Memory** | A saved fact, not the chat log. Example: “this guest prefers Arabic”. |
| **Workflow** | A multi-step job (do A, then B). Not the chat popup. |
| **Widget** | The chat popup on a public website. |
| **Channel account** | One messaging connection: the web widget, or one WhatsApp number (Meta Cloud or a linked-device sidecar). |
| **Embed token** | A site key that starts with `wgt_`. Only sites you list may use the widget. |
| **Provider** | Who runs the model: OpenAI, Anthropic, Gemini, or Ollama (on your machine). |
| **Model** | The exact brain, e.g. `gpt-4.1-mini` or `qwen3:8b`. |
| **Gate** | A yes/no rule: “is this logged-in user allowed into admin?” |
| **Host app** | Your Laravel project. You `composer require` this package into it. |

---

## Two situations

Pick the one that matches you. The package commands are the same. What changes is **what you already have**.

### A. Fresh project (empty Laravel)

You are starting a new app, or you just ran `laravel new`.

1. Create the Laravel app (`laravel new my-app` or your usual way). PHP 8.3+, Laravel 12+.
2. Run the install commands below in that app.
3. Paste the **starter** keys into `.env` and add your AI key.
4. `php artisan migrate` creates only Agentic tables (plus Laravel’s default users table if you kept it).
5. Open `/agentic/admin`, create tool → skill → agent, then put the widget on `welcome.blade.php` or any page.
6. On a live server you still need **your** login (Laravel `auth`) and a **gate** — a fresh app has no staff users until you add them.

### B. Finished project (app already in production or almost)

You already have users, routes, Blade/React, maybe a queue, maybe Sanctum. **Do not replace any of that.** Agentic is a Composer package: it adds tables and routes; it does not take over your app.

1. In the **existing** project root: run the same install commands below.
2. **Append** the starter keys to your current `.env`. Do not replace `APP_KEY`, database, or mail settings.
3. `php artisan migrate` only adds `agentic_*` tables. Your old tables stay.
4. Admin is at `/agentic/admin` (or change the prefix). Point the admin **gate** at your real users (`Gate::define('viewAgentic', …)`).
5. Drop `<x-agentic-widget>` on a layout you already have (header/footer stay yours).
6. PHP tools should call **your** services (`App\Services\…`), not new standalone apps.
7. If you already run `queue:work` / Horizon, you do not start a second queue system — Agentic uses the same `QUEUE_CONNECTION`.
8. If the chat popup lives on another domain, add that origin on the embed token and allow CORS to your API host.

After either path: extra `.env` keys and token cost live only in [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).

---

## Install (same commands for both)

```bash
composer require hatem-isnaad/agentic
php artisan agentic:install
```

**Interactive wizard (default):** choose deployment mode, AI provider, model, RAG, and credentials — answers are written to `.env`. Details: [INSTALL_WIZARD.md](./INSTALL_WIZARD.md).

Skip wizard (CI / manual `.env`):

```bash
php artisan agentic:install --quick
```

The wizard can also publish assets, migrate, and create an embed token when you confirm at the end.

Manual follow-up if you used `--quick`:

```bash
php artisan vendor:publish --tag=ai-config
php artisan vendor:publish --tag=agentic-widget-assets --force
php artisan vendor:publish --tag=agentic-admin-assets --force
php artisan migrate
```

Minimal `.env` if you configure by hand (or use `AGENTIC_MODE` — see handbook):

```env
AGENTIC_MODE=local
AGENTIC_ENABLED=true
AGENTIC_AI_PROVIDER=openai
AGENTIC_AI_MODEL=gpt-4.1-mini
OPENAI_API_KEY=sk-...
AGENTIC_ADMIN_WEB_ENABLED=true
AGENTIC_PERMISSION_DEFAULT=deny
AGENTIC_WIDGET_ENABLED=true
AGENTIC_WIDGET_BROADCAST_DRIVER=pusher
```

Need another key, a bigger/smaller limit, or token cost? **Only** [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).

Then open:

- Admin: `https://your-app.test/agentic/admin`
- Demo chat: `https://your-app.test/agentic/widget?agent=YOUR_AGENT_SLUG`

On your laptop this is open so you can work. On a live server you must log in, and you must say who is allowed (a **gate**). See [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) section 4.

---

## Which doc should I open?

| I want to… | Open this |
|------------|-----------|
| Understand words and the first install | This page |
| Any extra `.env` key, limits, or token cost | [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) — only catalog |
| Get a chatbot working in one sitting | [DEVELOPER_QUICKSTART.md](./DEVELOPER_QUICKSTART.md) |
| Put the popup on a real website | [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md) |
| How the widget gets replies (no word-by-word stream) | [WIDGET_EMBED_SDK.md](./WIDGET_EMBED_SDK.md) § Typing + async · [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) § Widget replies · [FRONTEND_IMPLEMENTATION_GUIDE.md](./FRONTEND_IMPLEMENTATION_GUIDE.md) §5–6 |
| Build a staff inbox in **my** admin (not Agentic’s) | [STAFF_INBOX.md](./STAFF_INBOX.md) |
| Connect WhatsApp numbers (Meta now, link-device later) | [CHANNELS.md](./CHANNELS.md) |
| Fresh app vs attach to a finished app | This page — **Two situations** |
| Host checklist after attach | [HOST_BOOTSTRAP.md](./HOST_BOOTSTRAP.md) |
| Go live safely | [PRODUCTION_CHECKLIST.md](./PRODUCTION_CHECKLIST.md) · [SECURITY.md](./SECURITY.md) |
| Do the same thing from UI **or** CLI (never need both) | [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md) — **UI or CLI** · [CONFIGURE_BY_CODE.md](./CONFIGURE_BY_CODE.md) |
| See package overview on GitHub | [../README.md](../README.md) |
