# Filament Themes

Themes for Filament panels, and a way for every person to pick their own. Free and open source (MIT).

- Repository: [github.com/packstub/filament-themes](https://github.com/packstub/filament-themes)
- Packagist: [packstub/filament-themes](https://packagist.org/packages/packstub/filament-themes)
- Support: [GitHub issues](https://github.com/packstub/filament-themes/issues)

![A Filament dashboard in the Aurora theme: glass cards over a soft sky-to-blush backdrop, a floating dock-style sidebar, gradient stat values](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/aurora.png)

## Features

- **[Two bundled themes](themes.md)**: *Aurora*, glass with a floating dock sidebar, and *Midnight*, dark with violet accents.
- **[A theme per panel](themes.md)**: each panel picks its themes and default; the default can follow the workspace.
- **[A theme per user](user-preferences.md)**: an Appearance page where each person picks their theme, previewed instantly.
- **[Write your own](creating-a-theme.md)**: a theme is a class with a palette, plus a stylesheet for a full redesign.
- **[Fits your brand](configuration.md#colour-overrides)**: your colours and CSS on top of any theme.

## Guides

| Guide | What it covers |
| --- | --- |
| [Installation](installation.md) | Requirements, the install command, publishing assets, registering the plugin |
| [Themes](themes.md) | The bundled themes, offering themes per panel, the default and the tenant hook |
| [User preferences](user-preferences.md) | The Appearance page, the `ThemePicker` field, storage, reading and writing a choice from code |
| [Creating a theme](creating-a-theme.md) | The `Theme` contract, palettes, stylesheets, preferred mode, shipping a theme as a package |
| [Configuration](configuration.md) | The fluent `ThemesPlugin` API, the config file, colour overrides, custom CSS and forced mode, events |

## At a glance

```bash
composer require packstub/filament-themes
php artisan packstub-themes:install
php artisan filament:assets
```

```php
use Packstub\Themes\ThemesPlugin;

$panel->plugin(
    ThemesPlugin::make()
        ->default('aurora')
        ->userSelectable(),
);
```

## Requirements

PHP 8.2+, Laravel 12 or 13, Filament 5.

---

These pages are published at [packstub.dev/docs/filament-themes](https://packstub.dev/docs/filament-themes) from the package's `docs/` directory. Spotted a mistake? [Open a pull request](https://github.com/packstub/filament-themes).
