# Filament Themes

Themes for Filament panels, and a way for every person to pick their own. Free and open source (MIT).

- Repository: [github.com/packstub/filament-themes](https://github.com/packstub/filament-themes)
- Packagist: [packstub/filament-themes](https://packagist.org/packages/packstub/filament-themes)
- Support: [GitHub issues](https://github.com/packstub/filament-themes/issues)

## What you get

| Feature | What it means for you |
| --- | --- |
| **Two bundled themes** | *Aurora*, iridescent glass with a floating dock-style sidebar, in light and dark. *Midnight*, dark-first with violet-to-cyan accents. Both restyle Filament's own components, so nothing about how you build changes. |
| **A theme per panel** | Each panel chooses the themes it offers and its default. The default can be a closure, so a workspace can carry its own look. |
| **A theme per user** | An Appearance page in the user menu where each person picks the theme they see, saved per panel, previewed instantly, without touching their light/dark toggle. |
| **Open registry** | A theme is a class with an id, a label and a palette; add a stylesheet for a full redesign. Register it and every panel can offer it, from your app or from a package of your own. |

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
