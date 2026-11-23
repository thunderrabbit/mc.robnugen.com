# JavaScript libraries

Every script the site loads from somewhere else. mc has no `package.json`,
no build step and no `.js` files of its own; all JavaScript is inline in
templates.

## three.js

- **Version**: 0.145.0 (from the CDN URL)
- **Loaded at**: `templates/index.tpl.php:122`,
  `src="https://cdn.jsdelivr.net/npm/three@0.145.0/build/three.min.js"`
- **How**: CDN (jsDelivr, npm package `three`)
- **Needs**: nothing
- **Latest**: 0.187.0 (https://registry.npmjs.org/three/latest, checked
  2026-11-24). It no longer ships `build/three.min.js` as a classic script
  and has no `examples/js/` tree; it is ES-module only. The newest release
  that still serves the classic-script paths below is 0.160.0 for
  `build/three.min.js` (0.161.0 and later answer 404 on jsDelivr; checked
  with HEAD requests) and 0.147.0 for `examples/js/controls/` (0.148.0 and
  later answer 404).

## three.js OrbitControls (examples/js)

- **Version**: 0.145.0 (from the CDN URL; versioned with three.js)
- **Loaded at**: `templates/index.tpl.php:123`,
  `src="https://cdn.jsdelivr.net/npm/three@0.145.0/examples/js/controls/OrbitControls.js"`
- **How**: CDN (jsDelivr, same `three` package)
- **Needs**: three.js above (uses the global `THREE`); the two must stay on
  the same version
- **Latest**: same package, 0.187.0, but `examples/js/` was removed after
  0.147.0. The newest release that still has this classic-script file is
  0.147.0; going further means ES modules (`examples/jsm/controls/OrbitControls.js`
  plus an import map).

## Site's own scripts (all inline)

- `templates/index.tpl.php:125-1603`: the whole 3D visualizer: coordinate
  parser, scene/renderer, paths, chunk claims, curves, UI wiring. Uses the
  global `THREE`.
- `templates/admin/migrate_tables.tpl.php:2`: `applyMigration()`, a
  `fetch()` call to `/admin/apply_migration.php` for the migration dashboard.

No script is named from PHP (no `.js` strings in `*.php`), no import map
and no `import` statements.

## Notes for later steps

- three.js is the only library. Moving past 0.147.0 means switching from
  classic scripts to ES modules (import map or a bundler); there is no
  build step today.
- Both three.js files are on one version and must move together.
- The visualizer is one ~1480-line inline script that relies on the global
  `THREE`, so it would need to become a module (or get an `import`) to use
  newer three.js.
