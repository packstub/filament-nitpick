# Changelog

All notable changes to `packstub/filament-nitpick` are documented here.

## Unreleased

### Added

- A theme can configure the panel while it is active: `Theme::make()->serving(fn (Panel $panel) => ...)`, or a theme class implementing `ConfiguresPanel`. Top navigation, fonts, render hooks, colours, and a styling plugin added to the panel itself, so themes like Press work as their docs describe. The colours set there also give the theme its swatches in the picker. Pages, resources and widgets added there are left out and reported, since their routes exist before the request. Picking such a theme reloads the page. ([#2](https://github.com/packstub/filament-nitpick/issues/2))

### Fixed

- A theme plugin given to `Theme::make()->plugin()` that registers assets or render hooks for the whole app (`FilamentAsset::register()`, `FilamentView::registerRenderHook()`) no longer loads them on every panel and every theme. Its stylesheets become the theme's own, its other assets are still published by `filament:assets`, and its render hooks stay behind (add the plugin from `->serving()` to keep them).
- Picking a theme keeps its preferred light/dark mode after a reload on Filament 5.10.2 and later, which stores the mode under `filament:theme:mode` instead of `theme`. Both keys are written, so earlier 5.x releases keep working.

## 1.0.0-beta.1 — 2026-10-20

### Added

- `NitpickPlugin` for Filament 5 panels: the active theme sets the panel's colours, loads its stylesheet and its preferred light/dark mode.
- Two bundled themes: **Aurora** (iridescent glass, floating dock sidebar, light and dark variants) and **Midnight** (dark-first, violet-to-cyan accents, a coherent light mode).
- An open registry: any package or app registers a class implementing `Packstub\Nitpick\Contracts\Theme` (a palette-only theme is three methods) and every panel can offer it. `Theme::make('id')->colors([...])->stylesheet(...)` defines a theme inline, without a class file.
- A theme can be made of several stylesheets (`getStylesheets()`, `->stylesheets([...])`), linked in order and swapped together on preview.
- `Theme::make('id')->plugin(fn () => SomeThemePlugin::make())` offers a theme plugin from the Filament plugin directory as a choice in the picker: its palette and stylesheets are read from the plugin, which is not added to the panel. That covers the colours it sets on its panel or registers for the whole app, its CSS assets and a Vite theme (`->viteTheme()`).
- A stylesheet can be a CSS file, a URL, or `vite:<entry>` for an entry of the app's Vite build, so Tailwind-built themes join the picker too.
- Per-panel `->themes([...])` and `->default()`; the default accepts a closure, so it can follow the tenant.
- Per-user choice with `->userSelectable()`: an **Appearance** page linked from the user menu, a `ThemePicker` form field that drops into any profile form and saves a pick quietly (`->notify()` adds a notification), database or session storage, per panel.
- Live preview in the picker without a page reload; the user's own light/dark toggle is never overwritten on page load.
- `->colors()` overrides on top of any theme, `->customCss()` for per-project tweaks, `->forceMode()`.
- `ThemeChanged` event.
