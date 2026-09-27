# Publishing from the Cloud Agent VM

The canonical **cloud snapshot** of the headless platform (admin API, widget API, Sanctum + passkeys, docs, README) lives on GitHub branch:

**`cursor/cloud-platform-af3b`**

Open: https://github.com/hatem-isnaad/agentic/tree/cursor/cloud-platform-af3b

Local VM reference commit (includes valid `composer.lock`): `afb316b` on `cursor/sanctum-webauth-af3b` / `cursor/cloud-platform-af3b` (`c8c4dcf` adds lockfile locally only until pushed).

---

## Clone this branch (recommended)

```bash
git clone -b cursor/cloud-platform-af3b https://github.com/hatem-isnaad/agentic.git
cd agentic
composer install   # regenerates composer.lock (lockfile omitted on GitHub when MCP size limits block upload)
cp .env.example .env
./vendor/bin/phpunit
```

If you need a **byte-identical** lockfile to the VM, copy `composer.lock` from a full `git push` of local branch `cursor/cloud-platform-af3b` (commit `c8c4dcf`) once credentials work:

```bash
# On a machine that can push (after fetching from VM or shared copy)
git push -u origin cursor/cloud-platform-af3b
```

---

## Full history push (all branches aligned)

From a machine with GitHub credentials:

```bash
cd agentic
git fetch origin
git checkout cursor/cloud-platform-af3b   # or merge into main locally first
git push -u origin cursor/cloud-platform-af3b

# Optional: fast-forward main to the cloud snapshot
git checkout main
git merge cursor/cloud-platform-af3b
git push origin main
```

---

## Verify

```bash
git fetch origin
git log --oneline origin/cursor/cloud-platform-af3b -3
./vendor/bin/phpunit
```

Expected: **67 tests** (includes auth API tests).

---

## Cloud Agent limitations

- VM `git push` often returns **401** (no stored GitHub token).
- GitHub MCP `push_files` / `create_or_update_file` work for most source files; **`composer.lock` (~380 KB)** may exceed inline MCP payload limits — do not use `@/path` placeholders (they were committed literally in early sync attempts).
- After MCP sync, run **`composer install`** on the target machine or push the lockfile via normal `git push`.

---

## Related branches

| Branch | Purpose |
|--------|---------|
| `cursor/cloud-platform-af3b` | Full cloud VM snapshot (use this) |
| `cursor/sanctum-webauth-af3b` | Auth feature line (may lag; prefer cloud-platform) |
| `main` | Upstream; may contain separate PR merges (connections, runtime fixes) — reconcile with cloud branch as needed |

---

## Frontend docs

After checkout, see `docs/FRONTEND_IMPLEMENTATION_GUIDE.md` and `README.md` for Sanctum + passkeys and API examples.
