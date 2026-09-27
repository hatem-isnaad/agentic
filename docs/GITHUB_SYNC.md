# Publishing `main` to GitHub

Local development merged **admin + widget** into `main`. Feature branches `cursor/admin-panel-foundation-af3b` and `cursor/widget-platform-af3b` are reset to the same tip as `main`.

## Preferred (one command, full history)

From a machine with GitHub credentials:

```bash
cd agentic
git checkout main
git push origin main
git push origin main:cursor/widget-platform-af3b --force-with-lease
git push origin main:cursor/admin-panel-foundation-af3b --force-with-lease
```

## Verify

```bash
git fetch origin
git log --oneline origin/main -5
cd agentic && ./vendor/bin/phpunit
```

## Cloud Agent note

If the VM cannot `git push` (401), use GitHub MCP `push_files` in batches from `/tmp/sync-chunks/*.json` generated from local HEAD, or push from your laptop as above.

**Local tip (reference):** `df3d5a0` — 64 tests passing.
