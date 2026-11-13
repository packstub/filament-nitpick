# Upgrading

## From a 1.0 beta to 1.0

1.0 settles the names you build against, so they stay the same until 2.0. Most apps need two changes: the facade import, and the selector in any CSS of their own.

### Your code

| Beta | 1.0 |
| --- | --- |
| `use Packstub\Nitpick\Facades\Themes;` and `Themes::register(...)` | `use Packstub\Nitpick\Facades\Nitpick;` and `Nitpick::register(...)` |
| `->userSelectable()` | `->picker()` |
| `->userSelectable(fn (): bool => ..., userMenuItem: false)` | `->picker(condition: fn (): bool => ..., userMenuItem: false)` |
| `->appearancePage(MyAppearance::class)` | `->picker(page: MyAppearance::class)` |
| `isUserSelectable()`, `getAppearancePage()` | `hasPicker()`, `getPickerPage()` |
| A theme class with `getStylesheetPath(): ?string` | `getStylesheets(): array`, returning `[$path]` |

### Your CSS and scripts

| Beta | 1.0 |
| --- | --- |
| `html[data-packstub-theme="ocean"]` | `html[data-nitpick-theme="ocean"]` |
| `window.PackstubThemes.pick(...)` | `window.Nitpick.pick(...)` |
| the `packstub-theme-changed` event | `nitpick:theme-changed` |
| `<meta name="packstub-theme">` | `<meta name="nitpick-theme">` |
| the picker's `pst-theme-*` classes | `nitpick-*` (internal: style the picker through its own options where you can) |
| the bundled themes' `--pst-*` variables | `--nitpick-*` |

Nitpick's script is now a file, `nitpick.js`, published with its stylesheets: `php artisan filament:assets` (which `filament:upgrade` runs after `composer update`) puts it in place. A theme stylesheet of your own is published again under its new asset name: keep `php artisan filament:assets` in your deploy, as for every Filament plugin.

### The preferences table

1.0 adds an `accent` column (and lets `theme_id` be null, for a user who keeps an accent but follows the panel's default theme). Publish the new migration and run it; it skips a table that has the column already:

```bash
php artisan vendor:publish --tag="nitpick-migrations"
php artisan migrate
```

New installs keep choices in `nitpick_theme_preferences`. Your users' choices are in `theme_preferences`: keep that table by setting its name in `config/nitpick.php` (publish the config first with `php artisan vendor:publish --tag="nitpick-config"` if you have not):

```php
'table' => 'theme_preferences',
```

Or rename the table in a migration of your own and leave the config alone:

```php
Schema::rename('theme_preferences', 'nitpick_theme_preferences');
```
