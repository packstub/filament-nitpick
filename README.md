# Filament Themes

<div class="filament-hidden">

![Filament Themes: designed looks for Filament panels, one per panel and one per user](https://raw.githubusercontent.com/packstub/art/main/filament-themes/banner.jpg)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/packstub/filament-themes.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-themes)
[![Tests](https://img.shields.io/github/actions/workflow/status/packstub/filament-themes/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/packstub/filament-themes/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/packstub/filament-themes.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-themes)
[![License](https://img.shields.io/packagist/l/packstub/filament-themes.svg?style=flat-square)](https://github.com/packstub/filament-themes/blob/main/LICENSE.md)
[![Sponsor](https://img.shields.io/badge/sponsor-%E2%9D%A4-ea4aaa?style=flat-square&logo=githubsponsors&logoColor=white)](https://github.com/sponsors/icaliman)

</div>

Themes for Filament panels, and a way for every person to pick their own.

A theme is a colour palette plus a stylesheet that restyles the panel. This plugin ships two designed ones, lets any package add more, gives each panel a default, and adds an **Appearance** page where a signed-in user chooses the theme they see, the way they already choose light or dark mode.

![A Filament dashboard in the Aurora theme: glass cards over a soft sky-to-blush backdrop, a floating dock-style sidebar, gradient stat values](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/aurora.png)

## Features

- **[Two bundled themes](#the-bundled-themes)**: *Aurora*, glass with a floating dock sidebar, and *Midnight*, dark with violet accents.
- **[A theme per panel](#a-theme-per-panel)**: each panel picks its themes and default; the default can follow the workspace.
- **[A theme per user](#a-theme-per-user)**: an Appearance page where each person picks their theme, previewed instantly.
- **[Write your own](#writing-a-theme)**: a theme is a class with a palette, plus a stylesheet for a full redesign.
- **[Fits your brand](#colour-overrides-and-custom-css)**: your colours and CSS on top of any theme.
- **Dark mode and translations**: both bundled themes have a dark variant; every string lives in a language file.

## Compatibility

| Plugin | Filament | Laravel | PHP |
| --- | --- | --- | --- |
| 1.x | 5.x | 12.x, 13.x | 8.2+ |

## Installation

```bash
composer require packstub/filament-themes
php artisan packstub-themes:install
php artisan filament:assets
```

The install command publishes the config file and the `theme_preferences` migration and offers to run it. Then register the plugin:

```php
use Packstub\Themes\ThemesPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            ThemesPlugin::make()
                ->default('aurora')
                ->userSelectable(),
        );
}
```

That is the whole setup. The panel renders in Aurora, and **Appearance** appears in the user menu.

## The bundled themes

| Theme | Feel | Preferred mode |
| --- | --- | --- |
| `aurora` | Iridescent glass surfaces, a floating dock-style sidebar, sky and cyan accents. "Daybreak" in light mode, "Twilight" in dark. | Light |
| `midnight` | Deep near-black surfaces with a violet-to-cyan accent gradient. Keeps its shapes and palette on Filament's standard surfaces in light mode. | Dark |

![The same dashboard in the Midnight theme: deep near-black surfaces, violet-to-cyan stat values](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/midnight.png)

Both themes cover light and dark mode. This is Aurora after dark:

![A deals table in Aurora's dark variant, "Twilight": the same glass surfaces and dock sidebar on a deep night backdrop](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/aurora-dark.png)

A theme's preferred mode becomes the panel's default mode while the theme is active. A user who has toggled light or dark keeps their choice.

## A theme per panel

```php
use Packstub\Themes\Themes\Aurora;
use Packstub\Themes\Themes\Midnight;

ThemesPlugin::make()
    ->themes([Aurora::class, Midnight::class, 'ocean'])   // what this panel offers; defaults to every registered theme
    ->default(Midnight::class)                             // by class, id or instance
```

The default may be a closure, evaluated on every request:

```php
use Filament\Facades\Filament;

ThemesPlugin::make()
    ->default(fn (): string => Filament::getTenant()?->theme ?? 'aurora')
```

Register the plugin on every panel that should be themed. Each has its own offer, default and user choices.

## A theme per user

```php
ThemesPlugin::make()->userSelectable()
```

![The Appearance page: a card per theme with its swatches, label and description, the current one marked](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/appearance.png)

Turning this on:

- registers an **Appearance** page (`/appearance`) and links it from the user menu;
- saves each user's choice for this panel in the `theme_preferences` table (or the session, see [Configuration](#configuration));
- lets the picker preview a theme the moment it is clicked, no reload.

![The user menu with the Appearance link under the light and dark toggle](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/user-menu.png)

To offer the picker inside your own profile form instead, drop in the field. It fills itself with the user's current theme and saves on change:

```php
use Packstub\Themes\Filament\Forms\Components\ThemePicker;

ThemePicker::make(),
```

Use `->persist(false)` to handle the value yourself on submit, and `ThemesPlugin::get()->setUserTheme($id)` to save it. Reading and writing a choice from code:

```php
$plugin = ThemesPlugin::get();

$plugin->getActiveTheme();          // what this request renders
$plugin->getUserTheme();            // the signed-in user's choice, or null
$plugin->setUserTheme('midnight');  // save; null clears it
```

Saving fires `Packstub\Themes\Events\ThemeChanged`.

## Writing a theme

A palette-only theme is three methods:

```php
use Filament\Support\Colors\Color;
use Packstub\Themes\Theme;

class Ocean extends Theme
{
    public function getId(): string
    {
        return 'ocean';
    }

    public function getLabel(): string
    {
        return 'Ocean';
    }

    public function getColors(): array
    {
        return ['primary' => Color::Teal, 'gray' => Color::Slate];
    }
}
```

Add a stylesheet to restyle the panel. Scope every rule to `html[data-packstub-theme="ocean"]` so other panels stay untouched, and declare the mode the design is made for:

```php
use Filament\Enums\ThemeMode;

public function getStylesheetPath(): ?string
{
    return __DIR__.'/../resources/css/ocean.css';
}

public function getPreferredMode(): ?ThemeMode
{
    return ThemeMode::Light;
}
```

Register it from a service provider (or `AppServiceProvider::boot()`):

```php
use Packstub\Themes\Facades\Themes;

Themes::register(Ocean::class);
```

![A products table in Ocean, a palette-only theme: teal accents on Filament's standard surfaces](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/custom-theme.png)

The stylesheet is registered as a Filament asset, so `php artisan filament:assets` publishes it. From there the theme can be a panel default, offered to users, or shipped as its own Composer package.

## Colour overrides and custom CSS

```php
use Filament\Support\Colors\Color;

ThemesPlugin::make()
    ->colors(['primary' => Color::Indigo])       // on top of whichever theme is active
    ->customCss('css/admin-overrides.css')       // loaded after the theme's stylesheet
    ->forceMode(ThemeMode::Dark)                 // ignore the theme's preferred mode
```

## Configuration

`config/packstub-themes.php`:

| Key | Default | What it does |
| --- | --- | --- |
| `default` | `'aurora'` | The theme when a panel sets no `->default()` |
| `store` | `'database'` | `database` (one row per user and panel) or `session` (no migration, forgotten at logout) |
| `table`, `connection`, `user_model` | | Where the preferences live; the connection follows the user model, which is the central database under multi-database tenancy |
| `colors`, `custom_css`, `force_mode` | | App-wide equivalents of the plugin calls above |

## Documentation

Full docs at [packstub.dev/docs/filament-themes](https://packstub.dev/docs/filament-themes):

- [Installation](https://packstub.dev/docs/filament-themes/installation)
- [Themes](https://packstub.dev/docs/filament-themes/themes)
- [User preferences](https://packstub.dev/docs/filament-themes/user-preferences)
- [Creating a theme](https://packstub.dev/docs/filament-themes/creating-a-theme)
- [Configuration](https://packstub.dev/docs/filament-themes/configuration)

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](https://github.com/packstub/filament-themes/blob/main/CHANGELOG.md).

## Security

If you discover a security issue, email [support@packstub.dev](mailto:support@packstub.dev) instead of using the issue tracker.

## Credits

- [Ion Caliman](https://github.com/icaliman)
- [All contributors](https://github.com/packstub/filament-themes/contributors)

## License

MIT. See [LICENSE.md](https://github.com/packstub/filament-themes/blob/main/LICENSE.md).
