# Configuration

## The fluent API

```php
use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\NitpickPlugin;

NitpickPlugin::make()
    ->themes([...])                              // what this panel offers; ids, classes, instances or a closure
    ->default('aurora')                          // id, class, instance or a closure
    ->userSelectable()                           // Appearance page + user-menu link; bool or closure
    ->userSelectable(userMenuItem: false)        // page only
    ->appearancePage(MyAppearance::class)        // your subclass of the page
    ->picker(fn (ThemePicker $picker) => $picker->descriptions()) // configure the page's picker
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
| `table` | `'theme_preferences'` | The preferences table |
| `connection` | `null` | Its connection; null follows the user model's connection |
| `user_model` | `null` | The user model; null uses the default auth provider's |
| `colors` | `[]` | App-wide colour overrides, same shape as `->colors()` |
| `custom_css` | `null` | App-wide extra stylesheet, path relative to `public/` or a URL |
| `force_mode` | `null` | `'light'`, `'dark'` or `'system'` |

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

The file is linked after the active theme's stylesheet, so your rules win on equal specificity. Scope them to `[data-packstub-theme="…"]` when a tweak should only apply to one theme.

## Events

| Event | When | Payload |
| --- | --- | --- |
| `Packstub\Nitpick\Events\ThemeChanged` | After a user's choice is saved or cleared | `$user`, `$panelId`, `$theme` (null when cleared) |

## Translations

```bash
php artisan vendor:publish --tag="nitpick-translations"
```

Strings live under `nitpick::themes`: the Appearance page title and subheading, the picker label, the saved notification, and the bundled themes' labels and descriptions.

## Assets on deploy

Theme stylesheets are Filament assets. Keep `php artisan filament:assets` in your deploy step, as for every Filament plugin, so a new theme's CSS is published.
