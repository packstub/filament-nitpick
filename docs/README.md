# Nitpick

Themes for Filament panels, and a way for every person to pick their own. Free and open source (MIT).

- Repository: [github.com/packstub/filament-nitpick](https://github.com/packstub/filament-nitpick)
- Packagist: [packstub/filament-nitpick](https://packagist.org/packages/packstub/filament-nitpick)
- Support: [GitHub issues](https://github.com/packstub/filament-nitpick/issues)

![One Filament dashboard sweeping through the picker's themes, light and dark in turn: Aurora and Midnight (bundled), Kinetics, Abyss, Nord, Shadcn, Ocean and Noreviq](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/theme-sweep.gif)

Two designed themes ship with the plugin, and a theme plugin from the [Filament plugin directory](https://filamentphp.com/plugins), free or paid, joins them in the picker with one line:

```php
use Noreviq\FilamentTheme\NoreviqThemePlugin;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

// AppServiceProvider::boot()
Themes::register(
    Theme::make('noreviq')->plugin(fn () => NoreviqThemePlugin::make()),
);
```

## Features

- **[Two bundled themes](themes.md)**: *Aurora*, glass with a floating dock sidebar, and *Midnight*, dark with violet accents.
- **[Themes from the plugin directory](themes.md#themes-from-the-filament-plugin-directory)**: install a free or paid theme plugin and register it in one line; it joins the picker. Ready-made CSS and Vite-built themes alike.
- **[A theme per panel](themes.md)**: each panel picks its themes and default; the default can follow the workspace.
- **[A theme per user](user-preferences.md)**: an Appearance page where each person picks their theme, previewed instantly.
- **[Write your own](creating-a-theme.md)**: a theme is a class with a palette, plus a stylesheet for a full redesign; `Theme::make()` defines one inline.
- **[Fits your brand](configuration.md#colour-overrides)**: your colours and CSS on top of any theme.

## Guides

| Guide | What it covers |
| --- | --- |
| [Installation](installation.md) | Requirements, the install command, publishing assets, registering the plugin |
| [Themes](themes.md) | The bundled themes, themes from the Filament plugin directory (a step-by-step example, Vite-built themes), offering themes per panel, the default and the tenant hook |
| [User preferences](user-preferences.md) | The Appearance page, the `ThemePicker` field, storage, reading and writing a choice from code |
| [Creating a theme](creating-a-theme.md) | The `Theme` contract, palettes, stylesheets, preferred mode, `Theme::make()` for a theme without a class file, themes from the Filament plugin directory, configuring the panel, shipping a theme as a package |
| [For theme authors](theme-authors.md) | Writing a Filament theme plugin that works well in Nitpick: configuring the panel you are given, colours in `register()`, publishing assets, joining the picker on install |
| [Configuration](configuration.md) | The fluent `NitpickPlugin` API, the config file, colour overrides, custom CSS and forced mode, events |

## At a glance

```bash
composer require "packstub/filament-nitpick:^1.0@beta"
php artisan nitpick:install
php artisan filament:assets
```

```php
use Packstub\Nitpick\NitpickPlugin;

$panel->plugin(
    NitpickPlugin::make()
        ->default('aurora')
        ->picker(),
);
```

## Requirements

PHP 8.3+, Laravel 12 or 13, Filament 5.

---

These pages are published at [packstub.dev/docs/filament-nitpick](https://packstub.dev/docs/filament-nitpick) from the package's `docs/` directory. Spotted a mistake? [Open a pull request](https://github.com/packstub/filament-nitpick).
