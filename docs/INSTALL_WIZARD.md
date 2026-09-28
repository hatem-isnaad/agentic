# Interactive install wizard

After `composer require hatem-isnaad/agentic`, run:

```bash
php artisan agentic:install
```

In a normal terminal (interactive), this starts a **wizard** that:

1. Publishes `config/agentic.php` (thin host overrides only — defaults come from the package).
2. Asks **multiple-choice** questions (deployment mode, AI provider, model, RAG, vector store, widget realtime, theme).
3. Asks for **secrets** only when needed (API keys, Pusher secret).
4. **Merges** answers into your existing `.env` (does not wipe `APP_KEY` or database settings).
5. Optionally publishes admin/widget assets, runs `migrate`, and creates a `wgt_…` embed token.

## Deployment mode (`AGENTIC_MODE`)

| Choice | Meaning |
|--------|---------|
| **local** | Laptop: admin SPA + widget demo; admin API without Sanctum |
| **production** | Server: admin + widget API; Sanctum + embed token |
| **widget** | Server: only `/api/agentic/widget/*`; no admin or other APIs |

## Non-interactive / CI

```bash
php artisan agentic:install --quick
```

Skips the wizard and prints manual next steps.

Force wizard in CI (rare):

```bash
php artisan agentic:install --wizard
```

## Re-run wizard

Safe to run again — it updates Agentic-related keys in `.env` and appends any missing ones under:

```env
# --- Agentic (php artisan agentic:install) ---
```

## After install

```bash
php artisan config:clear
php artisan agentic:rag-validate
```

Open admin: `/agentic/admin` (when mode is `local` or `production`).

More keys: [DEVELOPER_HANDBOOK.md](./DEVELOPER_HANDBOOK.md).
