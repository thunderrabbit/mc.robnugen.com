# CLAUDE.md

Guidance for Claude Code working in this repo.

## What this is
A **Minecraft coordinate 3D visualizer** (live at `mc.robnugen.com`), built on a
minimalist custom PHP framework for DreamHost. Users paste Minecraft coords and
see them in an interactive 3D space with paths, chunk claims, and minecart-track
curves.

- **Frontend heart**: `templates/index.tpl.php` (~1600 lines) — the 3D visualizer,
  coord parser, and renderer. Most visualizer work happens here.
- **Coord/curve APIs**: `wwwroot/api/*.php` (load/save/list coords & curves).
- **Curve generation**: Python scripts at repo root (`coaster_coordination*.py`,
  `find_duplicate_curves*.py`, `calculate_anchors.py`, …) read/write `curves/`,
  `curves_north/`, `curves_deleted/`.

## Spec / status docs — read before re-deriving a feature
- `GET_CHUNKY.md` — chunk-claim visualization (impl status + index.tpl.php line refs)
- `WELCOME_ANON.md` — `/sample/` anonymous-viewer flow
- `KEEP_COORDS.md` — preserve textarea coords across login/register
- `GET_STARTED.md` — auto-load demo data for new users
- `README.md` — user-facing overview

## Git guardrail
Claude cannot commit/merge/push on `master` — a git wrapper blocks it (exit 77,
logged to `~/.claude/git-wrapper.log`). Work on a feature branch; Rob integrates.

## Live-site login
Test/agent login creds for `https://mc.robnugen.com/` are in `.SECRET_LOGIN_FOR_LLM`
(gitignored). Use them to log in and exercise the saved-coords/admin flows.

## Framework facts (not obvious from the tree)
- **Config**: create `classes/Config.php` from `classes/ConfigSample.php` (gitignored).
- **Autoloader**: `\Mlaphp\Autoloader` maps namespace separators to dirs under
  `classes/` (`Auth\`, `Database\`, `Mlaphp\`). No composer/npm.
- **Bootstrap**: every PHP file (not templates or prepend itself) includes
  `prepend.php` via:
  ```php
  preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
  include_once $matches[1] . '/prepend.php';
  ```
  `prepend.php` enables on-screen errors, registers the autoloader, runs DB checks,
  and redirects to `/login/register.php` when no users exist.
- **Templates**: `classes/Template.php` nests layouts via `grabTheGoods()`; files
  use `.tpl.php`.
- **Auth**: cookie/session login stored in DB; `Auth\IsLoggedIn`, IP via `Auth\IPBin`.

## Migrations
- `db_schemas/NN_name/create_*.sql`; applied set tracked in `applied_DB_versions`.
- Only prefixes `00` and `01` auto-apply. **`02_mc_coords` and later must be applied
  manually** at `/admin/migrate_tables.php`. Rollbacks are manual via PHPMyAdmin.

## Checks (phpcs, PHPStan, Unit suite)
- Dev-only composer tooling; nothing in `vendor/` deploys. Lemur 13 has no
  composer, so install with:
  `docker run --rm -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app composer:2 composer install --no-interaction`
- `.githooks/pre-commit` runs phpcs + PHPStan on the staged tree in
  `standards-codeception-runner:php8.5`. Enable once per clone:
  `git config core.hooksPath .githooks`.
- Unit suite: `~/work/rob/standards-mcp/run-unit.sh "$PWD"` (tests in
  `Tests/Unit/`, bootstrap skips `prepend.php`). PHPStan is at level 4, no
  baseline; phpcs excludes are listed in `phpcs.xml`.

## Debugging & deploy
- `?debug=1` on any URL; `print_rob($var)` for formatted dumps; errors print to screen.
- `scp_files_to_dh.sh` (gitignored, create locally) watches files and SCPs to
  `barefoot_rob@drc:/home/username/domain.com/`.
