# Configuration

## The fluent API

```php
use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Enums\Transition;
use Packstub\Nitpick\NitpickPlugin;

NitpickPlugin::make()
    ->themes([...])                              // what this panel offers; ids, classes, instances or a closure
    ->default('aurora')                          // id, class, instance or a closure
    ->picker(                                    // each user picks their theme: the Appearance modal and page
        using: fn (ThemePicker $picker) => $picker->descriptions(), // configure the cards in the modal and on the page
        condition: fn (): bool => true,          // bool or closure: who gets the picker
        modal: false,                            // the user menu links the Appearance page instead of opening the modal
        userMenuItem: false,                     // no user-menu item: the page only
        page: MyAppearance::class,               // your subclass of the page
        transition: Transition::Sweep,           // the swap animation: Fade (default), Slide, Sweep, or false for none
        accent: true,                            // each user picks an accent colour too
        manage: fn (): bool => auth()->user()->isAdmin(), // who sets the theme for everyone (and per tenant)
    )
    ->colors(['primary' => Color::Indigo])       // slots applied on top of every theme
    ->customCss('css/admin-overrides.css')       // loaded after the theme's stylesheet
    ->forceMode(ThemeMode::Dark)                 // ignore the theme's preferred mode; null hands it back
```

Everything is per panel. `NitpickPlugin::get()` returns the current panel's instance.

## The config file

```bash
php artisan vendor:publish --tag="nitpick-config"
```

| Key | Default | What it does |
| --- | --- | --- |
| `default` | `'aurora'` | The theme a panel uses when its plugin sets no `->default()` |
| `store` | `'database'` | `database` or `session`; see [User preferences](user-preferences.md#where-the-choice-is-kept) |
| `table` | `'nitpick_theme_preferences'` | The preferences table |
| `panel_table` | `'nitpick_panel_themes'` | What managers set for everyone (`->picker(manage: ...)`) |
| `connection` | `null` | Its connection; null follows the user model's connection |
| `user_model` | `null` | The user model; null uses the default auth provider's |
| `user_key_type` | `null` | `'int'`, `'uuid'` or `'ulid'`: the type of the table's `user_id`; null follows the app's morph key type (`Schema::morphUsingUuids()`), else int. Set it before migrating |
| `colors` | `[]` | App-wide colour overrides, same shape as `->colors()` |
| `custom_css` | `null` | App-wide extra stylesheet, path relative to `public/` or a URL |
| `force_mode` | `null` | `'light'`, `'dark'` or `'system'` |
| `transition` | `'fade'` | The swap animation: `'fade'`, `'slide'`, `'sweep'` or `null`; see [User preferences](user-preferences.md#swap-animations) |

The plugin call always wins over the config key.

## Colour overrides

Overrides sit on top of whichever theme is active, so a brand colour survives a theme switch:

```php
NitpickPlugin::make()->colors(['primary' => Color::Indigo])
```

Slots you do not override keep the theme's own values. The order is: theme palette, then `colors` from the config, then `->colors()`.

## Custom CSS

For per-project tweaks without forking a theme:

```php
NitpickPlugin::make()->customCss('css/admin-overrides.css')
```

The file is linked after the active theme's stylesheet, so your rules win on equal specificity. Scope them to `[data-nitpick-theme="…"]` when a tweak should only apply to one theme.

## Events

| Event | When | Payload |
| --- | --- | --- |
| `Packstub\Nitpick\Events\ThemeChanged` | After a user's choice is saved or cleared | `$user`, `$panelId`, `$theme` (null when cleared) |
| `Packstub\Nitpick\Events\PanelThemeChanged` | After a manager changes the theme, accent or lock for everyone | `$panelId`, `$tenant` (null for the whole panel), `$theme` (a `PanelTheme`) |
| `Packstub\Nitpick\Events\AccentChanged` | After a user's accent colour is saved or cleared | `$user`, `$panelId`, `$accent` (a colour name or hex, null when cleared) |

## Translations

```bash
php artisan vendor:publish --tag="nitpick-translations"
```

Strings live under `nitpick::themes`: the Appearance page title and subheading, the picker label, the saved notification, and the bundled themes' labels and descriptions.

## Content Security Policy

Nitpick's script is a Filament asset (`js/packstub/filament-nitpick/nitpick.js`), so a policy that allows your own scripts allows it. A few lines run inline in the head, before the page paints (they set the active theme on `<html>`), next to two inline `<style>` blocks with the palettes and the swap's cover. When your app sets a nonce with `Vite::useCspNonce()`, all of them carry it, as Vite's own tags do.

## Octane

Nitpick works under Laravel Octane with nothing to configure. A user's theme, and a `->default()` closure, are worked out on each request, and a theme that [configures the panel](creating-a-theme.md#configuring-the-panel) leaves it as it found it when the request ends, so the next request on the same worker starts clean.

## Assets on deploy

Theme stylesheets are Filament assets. Keep `php artisan filament:assets` in your deploy step, as for every Filament plugin, so a new theme's CSS is published.
