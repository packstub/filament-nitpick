# Changelog

All notable changes to `packstub/filament-nitpick` are documented here.

## Unreleased

**Upgrading:** names settle for 1.0, see [Upgrading](docs/upgrading.md): the `Nitpick` facade (was `Themes`), `html[data-nitpick-theme]` in your CSS, `window.Nitpick` in your scripts, and `'table' => 'theme_preferences'` in the config to keep your users' choices.

### Changed

- The picker's script is a cached file, `nitpick.js`, published by `php artisan filament:assets`, instead of 400 lines inlined into every page; a few lines stay inline to set the theme before the page paints. The inline script and styles carry the app's CSP nonce when it sets one (`Vite::useCspNonce()`). ([#22](https://github.com/packstub/filament-nitpick/issues/22))
- Requires Filament 5.8.3 or later, the first Filament 5 release without a published security advisory, and the lowest version CI now tests on. ([#21](https://github.com/packstub/filament-nitpick/issues/21))
- Every public name says Nitpick: the `data-nitpick-theme` attribute, `window.Nitpick`, the `nitpick:theme-changed` event, the `Nitpick` facade and, for new installs, the `nitpick_theme_preferences` table. They stay the same until 2.0. ([#18](https://github.com/packstub/filament-nitpick/issues/18))

### Fixed

- In an SPA app, the picker kept the first panel's themes after a navigation to another panel, so a theme only that panel offers could not be picked.

### Added

- A **Panel default** card, first in the picker: it says which theme the panel uses by default, and picking it clears the user's choice, so they follow the default again, including when it changes later. `ThemePicker::make()->defaultOption(false)` leaves it out. ([#23](https://github.com/packstub/filament-nitpick/issues/23))
- Apps whose users have UUID or ULID keys can save a theme: set `'user_key_type' => 'uuid'` (or `'ulid'`) in `config/nitpick.php` before running the migration. Left `null`, the column follows the app's morph key type, as before. ([#19](https://github.com/packstub/filament-nitpick/issues/19))

### Fixed

- Under Octane, a user's theme is worked out on every request: it was remembered by the worker, so after a pick the other workers kept serving the old theme, and a `->default()` closure that follows the tenant kept the first tenant's theme. ([#20](https://github.com/packstub/filament-nitpick/issues/20))

### Removed

- `userSelectable()`, `appearancePage()`, `isUserSelectable()` and `getAppearancePage()`, deprecated since beta.2: `->picker(...)` and `getPickerPage()` replace them. A theme gives its CSS with `getStylesheets()` only; `getStylesheetPath()` is gone.

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
