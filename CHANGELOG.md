# Changelog

All notable changes to `packstub/filament-nitpick` are documented here.

## 1.0.0 — 2026-11-13

The first stable release: the names you build against stay the same until 2.0. **Upgrading** from a beta: see [Upgrading](docs/upgrading.md) (the `Nitpick` facade, `data-nitpick-theme` in your CSS, `window.Nitpick` and the `nitpick:theme-changed` event in your scripts, `->picker()` for the removed `userSelectable()` and `appearancePage()`, `getStylesheets()` for `getStylesheetPath()`, `'table' => 'theme_preferences'` to keep your users' choices, `php artisan filament:assets`, and the new migrations published and run).

- Each theme previewed in light and dark on its card, and a **Panel default** card to go back to the panel's theme
- Accent colours: each user picks a colour for buttons and links, checked for contrast
- The theme for everyone: managers set the theme and accent for the panel or a tenant, and can lock it
- Nine languages, right-to-left panels, and a cached script that works with a Content Security Policy
- Fixed: under Octane, a new pick is served by every worker at once; in SPA apps, the picker follows a navigation to another panel
- Works with UUID and ULID user keys; requires Filament 5.8.3 or later

## 1.0.0-beta.3 — 2026-10-28

A theme that breaks no longer locks anyone out of the panel.

- A theme that fails to set up the panel falls back to the panel's default theme, with a notice; the choice is kept
- A theme that makes every page fail (Rail without a `notifications` table) is set aside for that user for 10 minutes
- Themes with unknown colours show no swatches instead of three empty circles
- Fixed: the Appearance modal no longer hides under a theme's sidebar (MeUI)
- New [Tested themes](docs/tested-themes.md) page: 33 free community themes checked in the picker
- More help in the [Themes](docs/themes.md) and [For theme authors](docs/theme-authors.md) docs

## 1.0.0-beta.2 — 2026-10-21

Themes can now set up the panel itself, so theme plugins work the way their own docs describe.

- `Theme::make()->panel(...)`: top navigation, fonts, render hooks, colours and plugins while the theme is active
- The Appearance picker opens in a modal from the user menu (`->picker(modal: false)` keeps the page)
- A pick animates under a short cover: `Transition::Fade` (default), `Slide`, `Sweep` or `false`
- Shorter theme cards: the description becomes a tooltip unless you call `->descriptions()`
- Fixed: modals clipped under a blurred topbar, and the light/dark mode after a pick (Filament 5.10.2+ too)

**Upgrading:** `->picker()` now takes every picker option; `->userSelectable()` and `->appearancePage()` still work but are deprecated and go in 1.0. `Theme::make()->plugin()` now adds the plugin to the panel while its theme is active (picking it reloads the page), so its stylesheets and colours no longer come back from `getStylesheets()` and `getColors()`.

## 1.0.0-beta.1 — 2026-10-20

First beta of Nitpick: designed themes for Filament 5 panels, a default per panel and a choice for each user.

- Two bundled themes: Aurora and Midnight
- An open registry: any package or app can add a theme, as a class or inline with `Theme::make()`
- Theme plugins from the Filament directory as a choice in the picker, with `->plugin()`
- Stylesheets as CSS files, URLs or Vite entries, so Tailwind-built themes work too
- A default per panel (it can follow the tenant), and an Appearance page and `ThemePicker` field for users
- Live preview, plus `->colors()`, `->customCss()` and `->forceMode()` for your own tweaks

Docs: https://packstub.dev/docs/filament-nitpick
