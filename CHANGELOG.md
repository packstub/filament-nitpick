# Changelog

All notable changes to `packstub/filament-themes` are documented here.

## 1.0.0 — Unreleased

### Added

- `ThemesPlugin` for Filament 5 panels: the active theme sets the panel's colours, loads its stylesheet and its preferred light/dark mode.
- Two bundled themes: **Aurora** (iridescent glass, floating dock sidebar, light and dark variants) and **Midnight** (dark-first, violet-to-cyan accents, a coherent light mode).
- An open registry: any package or app registers a class implementing `Packstub\Themes\Contracts\Theme` (a palette-only theme is three methods) and every panel can offer it. `Theme::make('id')->colors([...])->stylesheet(...)` defines a theme inline, without a class file.
- Per-panel `->themes([...])` and `->default()`; the default accepts a closure, so it can follow the tenant.
- Per-user choice with `->userSelectable()`: an **Appearance** page linked from the user menu, a `ThemePicker` form field that drops into any profile form, database or session storage, per panel.
- Live preview in the picker without a page reload; the user's own light/dark toggle is never overwritten on page load.
- `->colors()` overrides on top of any theme, `->customCss()` for per-project tweaks, `->forceMode()`.
- `ThemeChanged` event.
