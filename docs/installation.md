# Installation

## Requirements

- PHP 8.3 or newer
- Laravel 12 or 13
- Filament 5

## 1. Require the package

```bash
composer require "packstub/filament-nitpick:^1.0@beta"
php artisan nitpick:install
```

The install command publishes the config file and the `theme_preferences` migration and offers to run it. The migration is only needed when users may pick a theme and the choice is kept in the database (the default). You can run the pieces yourself instead:

```bash
php artisan vendor:publish --tag="nitpick-config"
php artisan vendor:publish --tag="nitpick-migrations"
php artisan migrate
```

> [!NOTE]
> The preferences table follows your user model's connection, so under multi-database tenancy it lands in the central database next to the users. Set `connection` in the config file to pin it elsewhere.

## 2. Publish the stylesheets

Theme stylesheets are Filament assets. Publish them the same way as every other plugin's:

```bash
php artisan filament:assets
```

Add this to your deploy script if it is not there already. No Vite build is involved: the stylesheets are plain CSS that layers on top of Filament's own theme.

## 3. Register the plugin

```php
use Packstub\Nitpick\NitpickPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            NitpickPlugin::make()
                ->default('aurora')
                ->userSelectable(),
        );
}
```

- `->default()` is the theme people see until they pick one. It falls back to the `default` config key, then to the first offered theme.
- `->userSelectable()` adds the **Appearance** page and its user-menu link. Leave it out for a fixed look.

Register the plugin on every panel that should be themed. Each panel keeps its own offer, default and user choices.

## 4. Try it

1. Sign in and open the user menu. Choose **Appearance**.
2. Click a theme. The panel changes as you click, and the choice is saved.
3. Toggle light or dark mode from the topbar. Your toggle sticks, whichever theme is active.

Continue with [Themes](themes.md).
