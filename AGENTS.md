# packstub/filament-themes

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

A theme is a class implementing `Packstub\Themes\Contracts\Theme` (id, label, Filament colour palette, optional stylesheets, optional preferred light/dark mode); `Theme::make('id')` defines one inline, and its `themePlugin()` reads the palette and stylesheets (CSS assets, a Vite theme, app-wide colours) of a third-party Filament theme plugin from a scratch panel (`Support\PluginCapture`); a stylesheet is a file path, a URL or `vite:<entry>`. A stylesheet is plain CSS scoped to `html[data-packstub-theme="<id>"]` and registered as a Filament `Css` asset loaded on request, so `php artisan filament:assets` publishes it. The plugin sets the panel colours from the active theme (a closure evaluated at panel boot, so the signed-in user's preference is known), renders the stylesheet link and a small bootstrap script in `HEAD_END`, and never writes Filament's `theme` localStorage key on page load — only when the user picks a theme.

## Conventions

- Every change needs a test; `CHANGELOG.md` stays current.
- Never target Filament classes that no longer exist in v5 (`fi-btn-color-*`, `fi-fo-component-ctn`); colour classes are `fi-color-<name>`, the collapsed sidebar is `.fi-sidebar:not(.fi-sidebar-open)`.
