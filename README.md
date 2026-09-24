# Vistula Travel

A WordPress-first tourism website (tours, destinations, travel guide, booking, payment).
Architecture is documented in [`docs/`](docs/) — see `docs/project-architecture.md`, `docs/data-model.md`,
`docs/technical-decisions.md` and `docs/implementation-roadmap.md` (the source of truth for build order).

## Status

**Phase 0 (Architecture): approved.**
**Stage 1 (WordPress Foundation): blocked in the authoring sandbox — see below. Not yet completed anywhere.**

## ⚠ Stage 1 blocker (read this first)

Stage 1 requires an actual running WordPress install (PHP, a MySQL/MariaDB database, and either a
local web server or `wp env`/Docker) so its exit criteria — WordPress loads, clean permalinks work,
the timezone is active, the debug log is clean — can be genuinely verified. **The sandbox this repo
was prepared in has none of that and no network access to install it:**

| Requirement | Found in the sandbox |
|---|---|
| PHP | Not installed |
| MySQL / MariaDB | Not installed |
| WP-CLI | Not installed |
| Local web server (nginx/Apache) | Not installed |
| Network access to install any of the above, or to download WordPress core | Blocked (`apt-get update` → `403 Forbidden`; `github.com` and `wordpress.org` → `403 Forbidden`) |

Rather than fake a working install or silently skip verification, this repo only contains what could
be honestly prepared without those tools: version-control hygiene and the already-approved
architecture docs. **Everything else in Stage 1 needs to be run on a machine that actually has PHP,
MySQL/MariaDB, and internet access** — most likely yours. The steps below are exactly what's left.

### Finishing Stage 1 on your machine

1. **Get a local environment.** Any of these satisfy the architecture's requirements (PHP 8.3
   recommended, MySQL/MariaDB, WP-CLI): [Local](https://localwp.com/), `wp-env` (`@wordpress/env`),
   or a plain `docker compose` stack. Pick whichever you already use.
2. **Install WordPress core** into this repo's root (core itself stays git-ignored — see
   `.gitignore`), then delete the default sample page/post and the Hello Dolly plugin if present.
3. **Copy `.env.example` to `.env`** and fill in real values — a real DB password and **freshly
   generated** secret keys/salts from <https://api.wordpress.org/secret-key/1.1/salt/> (don't reuse
   the placeholders).
4. **Point `wp-config.php` at the environment**, e.g.:
   ```php
   define( 'DB_NAME', getenv( 'DB_NAME' ) );
   define( 'DB_USER', getenv( 'DB_USER' ) );
   define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );
   define( 'DB_HOST', getenv( 'DB_HOST' ) );
   define( 'DB_CHARSET', getenv( 'DB_CHARSET' ) ?: 'utf8mb4' );

   define( 'WP_HOME', getenv( 'WP_HOME' ) );
   define( 'WP_SITEURL', getenv( 'WP_SITEURL' ) );

   define( 'WP_DEBUG', getenv( 'WP_DEBUG' ) === 'true' );
   define( 'WP_DEBUG_LOG', getenv( 'WP_DEBUG_LOG' ) === 'true' );
   define( 'WP_DEBUG_DISPLAY', getenv( 'WP_DEBUG_DISPLAY' ) === 'true' );

   define( 'DISALLOW_FILE_EDIT', true ); // Stage 1 security baseline
   ```
   (Load `.env` however your local tool does — Local/`wp-env` read it automatically; with a plain
   PHP setup, `vlucas/phpdotenv` or your shell's `export $(cat .env | xargs)` both work.)
5. **Set permalinks** to *Post name* (Settings → Permalinks) and confirm they work (visit a page,
   not just the homepage).
6. **Set the timezone** to *Europe/Warsaw* (Settings → General).
7. **Set up local mail catching** (Mailpit, Mailhog, or your tool's built-in catcher) pointed at the
   `SMTP_HOST`/`SMTP_PORT` in `.env` — transport only; the contact form itself is Stage 9.
8. **Verify, don't assume:**
   - `wp-content/debug.log` stays empty through a normal front-end + admin visit
   - a non-home page loads with pretty permalinks (no `?p=123`)
   - Settings → General shows Europe/Warsaw and the admin footer time matches
   - `git status` is clean and `git log` shows no `.env`, no `wp-config.php`, no DB dump
9. **Commit** once all of the above is true — that's the real Stage 1 commit. This repo's current
   commit is scaffolding only (see `git log`); it is **not** that commit.

### Explicitly out of scope for Stage 1 (per `docs/implementation-roadmap.md`)

No custom theme, no `vistula-core` plugin, no CPTs, no Tours/Destinations/Blog/Booking/Payment
content, no design-system CSS/JS, no extra plugins beyond what's listed above. Those start at
Stage 2 (`docs/implementation-roadmap.md`), only once Stage 1 is genuinely verified.

## Repository layout

```
vistula-travel/
├─ docs/                  Phase 0 architecture — approved, unchanged
├─ .gitignore
├─ .env.example
└─ README.md
```

`wp-content/{themes/vistula-travel, plugins/vistula-core}/`, `seed/`, `tools/`, and
`.github/workflows/` are intentionally not created yet — they belong to Stage 2 onward
(architecture `docs/project-architecture.md` §19.1).

## Workflow

`change → test → git diff → commit → push`, trunk-based (`main` always deployable), Conventional
Commits. See `docs/project-architecture.md` §19.2.
