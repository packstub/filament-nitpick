# Changelog

All notable changes to `packstub/filament-nitpick` are documented here.

## Unreleased

### Added

- A theme that fails to configure the panel (its plugin throws in `register()` or `boot()`, as Kinetics does without its Vite theme, or its `->panel()` callback throws) no longer locks the user who picked it out of the panel. The failure is reported to the log, the panel is put back as it was, the panel's default theme is served for that request, and the user sees a notice that their theme is unavailable, with the picker one click away. Their choice is kept, so the theme comes back once it works. A default theme that fails still throws. ([#6](https://github.com/packstub/filament-nitpick/issues/6))
- [Tested themes](docs/tested-themes.md): 33 free community themes run through the picker on Filament 5.11, with the setup each one takes, and 8 colour switchers and branding tools that work beside Nitpick. The app that runs them is public at [packstub/nitpick-theme-compat](https://github.com/packstub/nitpick-theme-compat).

### Changed

- While a theme configures the panel, the plugins it added boot before its render hooks and assets are registered with Filament, so a plugin that fails in `boot()` leaves nothing behind.

### Fixed

- The Appearance modal is no longer hidden under a theme's sidebar: a sidebar given the modal's z-index (MeUI, 40 where Filament's has 30) paints over a modal opened from the user menu, which comes earlier in the DOM. The modal's overlay and window now take the z-index Filament gives a modal stacked on another one.

### Docs

- [Themes](docs/themes.md): a theme whose plugin needs the panel's `->viteTheme()` (`->panel()`, not `->stylesheet()`), a theme file without Filament's import (a two-line wrapper), install commands that edit the panel's theme file, packages that style every panel from their service provider (`dont-discover` and `->stylesheet()`; view overrides cannot be scoped from outside), `->colors()` and `->label()` for empty swatches and raw labels, and what happens when a theme cannot be served.
- [For theme authors](docs/theme-authors.md): prepend view overrides from the plugin, ship the files your assets point to, keep `<x-filament-actions::modals />` in a custom topbar and a custom sidebar below Filament's modal.

## 1.0.0-beta.2 — 2026-10-21

### Added

- A theme can configure the panel while it is active: `Theme::make()->panel(fn (Panel $panel) => ...)`, or a theme class implementing `ConfiguresPanel`. Top navigation, fonts, render hooks, colours, and a styling plugin added to the panel itself, so themes like Press work as their docs describe. The colours set there also give the theme its swatches in the picker. Pages, resources and widgets added there are left out and reported, since their routes exist before the request. The panel is put back when the request ends, so Octane starts the next request clean. Picking such a theme, or leaving one, reloads the page instead of previewing it half-applied. ([#2](https://github.com/packstub/filament-nitpick/issues/2))
- The Appearance item in the user menu opens the picker in a modal of fixed width and columns, so the cards stay put while a theme changes the panel's layout; a pick that reloads the page opens it again after the reload. The Appearance page stays; `->picker(modal: false)` links it instead. `ThemePicker::make()->columns([...])` fixes the columns anywhere. ([#4](https://github.com/packstub/filament-nitpick/issues/4))
- A pick animates in two steps: a black or white cover comes over the panel, the theme changes underneath (live or by a reload, which the next page starts under), and the cover lifts. `->picker(transition: Transition::Fade)` (the default), `Transition::Slide`, `Transition::Sweep` or `false`, also as `transition` in the config; skipped for visitors who prefer reduced motion. The cover lifts only once the page has stopped changing under it (a reloaded page waits for Livewire, the reopened modal and lazy widgets, at most 2.5 s), and the panel's own CSS transitions are held meanwhile, so nothing fades or pops in during the reveal. Picking again before the cover lifts changes to the newer theme under the same cover. `window.PackstubThemes.pick()` changes a theme with the transition from your own scripts; a theme that shows only after a page load is covered only with `pick(id, { reload: true })`, which expects your script to call `PackstubThemes.reload()` once the choice is saved. ([#4](https://github.com/packstub/filament-nitpick/issues/4))
- Theme cards in the picker are shorter: the description is the card's tooltip unless `ThemePicker::make()->descriptions()` shows it under the name, and `->swatches(false)` leaves the swatch strip out. A theme whose colours are unknown shows three empty swatches, so the cards line up. In the modal and on the Appearance page, configure the picker with `NitpickPlugin::make()->picker(fn (ThemePicker $picker) => $picker->descriptions())`.
- [For theme authors](docs/theme-authors.md): how to write a Filament theme plugin that works well in Nitpick, and how to join the picker as soon as both packages are installed.

### Changed

- `->picker()` turns the theme picker on and takes all its options: `->picker(fn (ThemePicker $picker) => ..., condition: ..., modal: false, userMenuItem: false, page: MyAppearance::class, transition: Transition::Sweep)`. An option left out keeps what an earlier call set. `->userSelectable()` and `->appearancePage()` (and `isUserSelectable()`, `getAppearancePage()`) still work and are deprecated, removed in 1.0.
- `Theme::make()->plugin()` adds the theme plugin to the panel while its theme is active, instead of reading its palette and stylesheets from it, so the plugin styles the panel exactly as its own docs describe: fonts, render hooks, layout options and whatever its `boot()` does now come along, and Tailwind-built plugins need nothing extra. While another theme is active, the plugin is not on the panel, so assets and render hooks it registers for the whole app no longer load on every panel and every theme. Its colours still give the theme its swatches in the picker, and `filament:assets` and `filament:upgrade` still publish its files. Picking such a theme, or leaving one, now reloads the page instead of previewing it live. A plugin's stylesheets are no longer returned by the theme's `getStylesheets()`, nor its colours by `getColors()`.

### Fixed

- A modal opened from the user menu is no longer clipped to the topbar under a theme that gives the topbar a `backdrop-filter`, `filter` or `transform` (Mia): while one is open, the bar drops those properties.
- The light/dark switcher in the user menu marks the mode a picked theme applies (Midnight's dark, say), instead of the mode the page loaded with.
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
