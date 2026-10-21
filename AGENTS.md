# packstub/filament-nitpick

Free Filament v5 plugin: designed themes for panels, a per-panel default, an open theme registry, and a per-user theme choice from the profile.

## Commands

```bash
composer test               # Pest suite
composer test:filter <name>
composer lint               # Pint
```

## Layout

- `src/` package code; `src/Themes/` the bundled themes (Aurora, Midnight); `resources/css/themes/<id>.css` their stylesheets.
- `config/`, `database/migrations/`, `resources/lang`, `resources/views`.
- `docs/` customer docs (synced to packstub.dev by CI) and `docs/images/` screenshots.
- `art/` brand/listing art sources.

## How a theme works

A theme is a class implementing `Packstub\Nitpick\Contracts\Theme` (id, label, Filament colour palette, optional stylesheets, optional preferred light/dark mode); `Theme::make('id')` defines one inline, and its `plugin()` adds a third-party Filament theme plugin to the panel while the theme is active (shorthand for `->panel(fn ($panel) => $panel->plugin(...))`). A scratch panel that is never served (`Support\PluginCapture`) is used only to read such a theme's colours for the picker's swatches and, when `filament:assets`/`filament:upgrade`/`filament:install` starts, to register its plugin's assets (loaded on request) so they are published; a stylesheet is a file path, a URL or `vite:<entry>`. A stylesheet is plain CSS scoped to `html[data-packstub-theme="<id>"]` and registered as a Filament `Css` asset loaded on request, so `php artisan filament:assets` publishes it. The plugin sets the panel colours from the active theme (a closure evaluated at panel boot, so the signed-in user's preference is known), renders the stylesheet link and a small bootstrap script in `HEAD_END`, and never writes Filament's light/dark localStorage key (`filament:theme:mode`, `theme` before 5.10.2) on page load — only when the user picks a theme. A theme that configures the panel (`->panel()`, `Contracts\ConfiguresPanel`) runs on `Filament::serving` for the active theme only (`Support\PanelServing`: registers what it added, takes pages back out, restores the panel when the request ends for Octane); picking one reloads the page. The picker opens in a modal from the user menu (`NitpickPlugin::MODAL_COLUMNS`, the page stays), and a pick runs through `PackstubThemes.pick()`: a cover (`#nitpick-cover-layer`, its shape animated with the Web Animations API on `transform`/`opacity` only, so it runs off the main thread; the sweep is a half-plane rotated about a point below the right corner, only through the angles where its edge crosses the panel and keyed by the share of the panel it has passed, so it never leaps where it is far from the point) comes in, the theme changes, the cover lifts; `PackstubThemes.reload()` waits for the cover and leaves a `nitpick:cover` note in sessionStorage, so the reloaded page starts under `html[data-nitpick-cover]::after` (static) until the layer takes over, and reopens the modal under the cover. A cover lifts only on a settled page (`settled()`: no DOM change for a moment, then frames on time; a reloaded page also waits for Livewire and the modal), or the reveal shows widgets popping in and drops its first frames. While a swap runs, `html[data-nitpick-still]` turns every CSS transition off (from the theme change until the cover is gone, and on a reloaded page until the modal is back too), or Filament's colour transitions ease the old theme into the new one under the reveal and it reads as a fade. Never animate cover properties through CSS variables on `<html>`: they inherit, so every frame restyles the whole page.

## Conventions

- Every change needs a test; `CHANGELOG.md` stays current.
- Never target Filament classes that no longer exist in v5 (`fi-btn-color-*`, `fi-fo-component-ctn`); colour classes are `fi-color-<name>`, the collapsed sidebar is `.fi-sidebar:not(.fi-sidebar-open)`.
