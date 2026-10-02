# Filament Themes

Themes for Filament panels, and a way for every person to pick their own. Free and open source (MIT).

- Repository: [github.com/packstub/filament-themes](https://github.com/packstub/filament-themes)
- Packagist: [packstub/filament-themes](https://packagist.org/packages/packstub/filament-themes)
- Support: [GitHub issues](https://github.com/packstub/filament-themes/issues)

![A Filament dashboard in the Aurora theme: glass cards over a soft sky-to-blush backdrop, a floating dock-style sidebar, gradient stat values](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/aurora.png)

Two designed themes ship with the plugin, and a theme plugin from the [Filament plugin directory](https://filamentphp.com/plugins), free or paid, joins them in the picker with one line:

```php
use Noreviq\FilamentTheme\NoreviqThemePlugin;
use Packstub\Themes\Facades\Themes;
use Packstub\Themes\Theme;

// AppServiceProvider::boot()
Themes::register(
    Theme::make('noreviq')->themePlugin(fn () => NoreviqThemePlugin::make()),
);
```

## Features

- **[Two bundled themes](themes.md)**: *Aurora*, glass with a floating dock sidebar, and *Midnight*, dark with violet accents.
- **[Themes from the plugin directory](themes.md#themes-from-the-filament-plugin-directory)**: install a free or paid theme plugin and register it in one line; it joins the picker. Ready-made CSS and Vite-built themes alike.
- **[A theme per panel](themes.md)**: each panel picks its themes and default; the default can follow the workspace.
- **[A theme per user](user-preferences.md)**: an Appearance page where each person picks their theme, previewed live: the new look opens out from the picked card.
- **[Write your own](creating-a-theme.md)**: a theme is a class with a palette, plus a stylesheet for a full redesign; `Theme::make()` defines one inline.
- **[Fits your brand](configuration.md#colour-overrides)**: your colours and CSS on top of any theme.

## Guides

| Guide | What it covers |
| --- | --- |
| [Installation](installation.md) | Requirements, the install command, publishing assets, registering the plugin |
| [Themes](themes.md) | The bundled themes, themes from the Filament plugin directory (a step-by-step example, Vite-built themes), offering themes per panel, the default and the tenant hook |
| [User preferences](user-preferences.md) | The Appearance page, the `ThemePicker` field, storage, reading and writing a choice from code |
| [Creating a theme](creating-a-theme.md) | The `Theme` contract, palettes, stylesheets, preferred mode, `Theme::make()` for a theme without a class file, themes from the Filament plugin directory, shipping a theme as a package |
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
