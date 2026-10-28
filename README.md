# Nitpick

<div class="filament-hidden">

![Nitpick: designed looks for Filament panels, one per panel and one per user](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/banner.jpg)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/packstub/filament-nitpick.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-nitpick)
[![Tests](https://img.shields.io/github/actions/workflow/status/packstub/filament-nitpick/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/packstub/filament-nitpick/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/packstub/filament-nitpick.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-nitpick)
[![License](https://img.shields.io/packagist/l/packstub/filament-nitpick.svg?style=flat-square)](https://github.com/packstub/filament-nitpick/blob/main/LICENSE.md)
[![Sponsor](https://img.shields.io/badge/sponsor-%E2%9D%A4-ea4aaa?style=flat-square&logo=githubsponsors&logoColor=white)](https://github.com/sponsors/icaliman)

</div>

Themes for Filament panels, and a way for every person to pick their own.

A theme is a colour palette plus a stylesheet that restyles the panel. This plugin ships two designed ones, lets any package add more, gives each panel a default, and adds an **Appearance** page where a signed-in user chooses the theme they see, the way they already choose light or dark mode.

It is also a home for the themes you already like. Install a theme plugin from the [Filament plugin directory](https://filamentphp.com/plugins), free or paid, and one line puts it in the picker beside the others:

```php
use Noreviq\FilamentTheme\NoreviqThemePlugin;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

// AppServiceProvider::boot()
Themes::register(
    Theme::make('noreviq')->plugin(fn () => NoreviqThemePlugin::make()),
);
```

![One Filament dashboard sweeping through the picker's themes, light and dark in turn: Aurora and Midnight (bundled), Kinetics, Abyss, Nord, Shadcn, Ocean and Noreviq](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/theme-sweep.gif)

## Features

- **[Two bundled themes](#the-bundled-themes)**: *Aurora*, glass with a floating dock sidebar, and *Midnight*, dark with violet accents.
- **[Themes from the plugin directory](#themes-from-the-filament-plugin-directory)**: install a free or paid theme plugin and register it in one line; it joins the picker. Ready-made CSS and Vite-built themes alike.
- **[A theme per panel](#a-theme-per-panel)**: each panel picks its themes and default; the default can follow the workspace.
- **[A theme per user](#a-theme-per-user)**: an Appearance modal in the user menu where each person picks their theme, with a fade, slide or sweep as it changes.
- **[Write your own](#writing-a-theme)**: a theme is a class with a palette, plus a stylesheet for a full redesign; `Theme::make()` defines one inline, and `->panel()` lets it configure the panel (top navigation, fonts) while it is active.
- **[Fits your brand](#colour-overrides-and-custom-css)**: your colours and CSS on top of any theme.
- **Dark mode and translations**: both bundled themes have a dark variant; every string lives in a language file.

## Compatibility

| Plugin | Filament | Laravel | PHP |
| --- | --- | --- | --- |
| 1.x | 5.x | 12.x, 13.x | 8.3+ |

## Installation

```bash
composer require "packstub/filament-nitpick:^1.0@beta"
php artisan nitpick:install
php artisan filament:assets
```

The install command publishes the config file and the `theme_preferences` migration and offers to run it. Then register the plugin:

```php
use Packstub\Nitpick\NitpickPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            NitpickPlugin::make()
                ->default('aurora')
                ->picker(),
        );
}
```

That is the whole setup. The panel renders in Aurora, and **Appearance** appears in the user menu.

## The bundled themes

Two themes ship with the plugin. Each has a light and a dark mode, and prefers one of them.

**Aurora** (`aurora`, prefers light): iridescent glass surfaces, a floating dock-style sidebar, sky and cyan accents. "Daybreak" in light mode, "Twilight" in dark.

![The same dashboard in Aurora, light mode ("Daybreak") on the left and dark mode ("Twilight") on the right: glass cards, a floating dock sidebar, gradient stat values](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/aurora-modes.png)

**Midnight** (`midnight`, prefers dark): deep near-black surfaces with a violet-to-cyan accent gradient. In light mode it keeps its shapes and palette on Filament's standard surfaces.

![The same dashboard in Midnight, light mode on the left and dark mode on the right: violet-to-cyan stat values, deep near-black surfaces in dark](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/midnight-modes.png)

A theme's preferred mode becomes the panel's default mode while the theme is active. A user who has toggled light or dark keeps their choice.

## Themes from the Filament plugin directory

The [Filament plugin directory](https://filamentphp.com/plugins) has a growing shelf of themes, free and paid, each with a character of its own. Any of them can sit in your picker beside the bundled ones, so a panel offers as many looks as you care to install.

Install the theme's package the way its own docs describe, then hand its plugin to a theme:

```php
use Noreviq\FilamentTheme\NoreviqThemePlugin;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

// AppServiceProvider::boot()
Themes::register(
    Theme::make('noreviq')->plugin(fn () => NoreviqThemePlugin::make()),
);
```

Run `php artisan filament:assets` and it is one more card on the Appearance page: a panel default, a per-user choice. The plugin joins the panel while its theme is active, and only then, so it styles the panel exactly as its own docs describe (palette, stylesheets, fonts, render hooks, layout) and leaves no trace while another theme is picked. Configure it the way its docs say (`NoreviqThemePlugin::make()->corners(...)`) and the theme follows. Picking it reloads the page.

The example is [Noreviq](https://filamentphp.com/plugins/yousef-al-jallad-noreviq-theme), an open-source teal and graphite theme by Yousef Al-Jallad, in three steps:

```bash
composer require noreviq/filament-theme   # 1. the theme's package
#                                           2. the Themes::register() call above
php artisan filament:assets               # 3. publish its CSS
```

Themes that build with Vite work the same way. For [Abyss](https://filamentphp.com/plugins/osama-nagi-abyss-theme), by Osama Nagi, add its stylesheet to `vite.config.js` as its docs ask, run `npm run build`, and register it:

```php
Themes::register(
    Theme::make('abyss')->plugin(fn () => FilamentAbyssThemePlugin::make()),
);
```

Register as many as you like, one `Theme::make()` each. [Themes](https://packstub.dev/docs/filament-nitpick/themes) walks through each kind of theme: ready-made CSS, a Vite theme, Tailwind source you compile ([Nord](https://filamentphp.com/plugins/andreia-bohner-nord-theme)), a palette without a plugin ([Shadcn](https://filamentphp.com/plugins/openplain-shadcn-theme)), and a theme that changes the layout ([Press](https://filamentphp.com/plugins/elemind-press-filament-theme)). [Tested themes](https://packstub.dev/docs/filament-nitpick/tested-themes) lists the 30+ community themes run through the picker, with the setup each one takes.

Writing a theme plugin? [For theme authors](https://packstub.dev/docs/filament-nitpick/theme-authors) covers the habits that make it work well in Nitpick, and in any app with more than one panel.

## A theme per panel

```php
use Packstub\Nitpick\Themes\Aurora;
use Packstub\Nitpick\Themes\Midnight;

NitpickPlugin::make()
    ->themes([Aurora::class, Midnight::class, 'ocean'])   // what this panel offers; defaults to every registered theme
    ->default(Midnight::class)                             // by class, id or instance
```

The default may be a closure, evaluated on every request:

```php
use Filament\Facades\Filament;

NitpickPlugin::make()
    ->default(fn (): string => Filament::getTenant()?->theme ?? 'aurora')
```

Register the plugin on every panel that should be themed. Each has its own offer, default and user choices.

## A theme per user

```php
NitpickPlugin::make()->picker()
```

![The Appearance page: a card per theme with its swatches, label and description, the current one marked](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/appearance.png)

Turning this on:

- adds an **Appearance** item to the user menu that opens the picker in a modal of fixed width and columns, and registers an Appearance page (`/appearance`) with the same picker;
- saves each user's choice for this panel in the `theme_preferences` table (or the session, see [Configuration](#configuration));
- changes the theme the moment a card is clicked, behind a short fade (`->picker(transition: Transition::Slide)`, `Transition::Sweep`, or `false` for none). A theme that changes the layout reloads the page under the same cover, and the modal opens again.

![The user menu with the Appearance link under the light and dark toggle](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/user-menu.png)

To offer the picker inside your own profile form instead, drop in the field. It fills itself with the user's current theme and saves on change:

```php
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;

ThemePicker::make(),
```

Each card shows the theme's swatches and name, with its description as the tooltip; `->descriptions()` shows it under the name, and `->picker(fn (ThemePicker $picker) => $picker->descriptions())` on the plugin does the same on the Appearance page. Use `->persist(false)` to handle the value yourself on submit, and `NitpickPlugin::get()->setUserTheme($id)` to save it. Reading and writing a choice from code:

```php
$plugin = NitpickPlugin::get();

$plugin->getActiveTheme();          // what this request renders
$plugin->getUserTheme();            // the signed-in user's choice, or null
$plugin->setUserTheme('midnight');  // save; null clears it
```

Saving fires `Packstub\Nitpick\Events\ThemeChanged`.

## Writing a theme

A palette-only theme is three methods:

```php
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Theme;

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
use Packstub\Nitpick\Facades\Themes;

Themes::register(Ocean::class);
```

![A products table in Ocean, a palette-only theme: teal accents on Filament's standard surfaces](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/custom-theme.png)

The stylesheet is registered as a Filament asset, so `php artisan filament:assets` publishes it. From there the theme can be a panel default, offered to users, or shipped as its own Composer package.

A small theme needs no class file. `Theme::make()` defines one where you register it:

```php
use Packstub\Nitpick\Theme;

Themes::register(
    Theme::make('nord')
        ->colors(['primary' => Color::Sky, 'gray' => Color::Slate])
        ->stylesheet(resource_path('css/themes/nord.css'))
        ->mode(ThemeMode::Dark),
);
```

An anonymous class (`new class extends Theme { ... }`) works too.

A theme plugin from the Filament plugin directory joins the picker with `Theme::make()->plugin()`; see [Themes from the Filament plugin directory](#themes-from-the-filament-plugin-directory).

A theme can also configure the panel while it is active, with top navigation or fonts:

```php
Theme::make('newsroom')
    ->colors(['primary' => Color::Red])
    ->panel(fn (Panel $panel) => $panel->topNavigation()->font('Fraunces')),
```

More in [Creating a theme](https://packstub.dev/docs/filament-nitpick/creating-a-theme).

## Colour overrides and custom CSS

```php
use Filament\Support\Colors\Color;

NitpickPlugin::make()
    ->colors(['primary' => Color::Indigo])       // on top of whichever theme is active
    ->customCss('css/admin-overrides.css')       // loaded after the theme's stylesheet
    ->forceMode(ThemeMode::Dark)                 // ignore the theme's preferred mode
```

## Configuration

`config/nitpick.php`:

| Key | Default | What it does |
| --- | --- | --- |
| `default` | `'aurora'` | The theme when a panel sets no `->default()` |
| `store` | `'database'` | `database` (one row per user and panel) or `session` (no migration, forgotten at logout) |
| `table`, `connection`, `user_model` | | Where the preferences live; the connection follows the user model, which is the central database under multi-database tenancy |
| `colors`, `custom_css`, `force_mode` | | App-wide equivalents of the plugin calls above |

## Documentation

Full docs at [packstub.dev/docs/filament-nitpick](https://packstub.dev/docs/filament-nitpick):

- [Installation](https://packstub.dev/docs/filament-nitpick/installation)
- [Themes](https://packstub.dev/docs/filament-nitpick/themes)
- [User preferences](https://packstub.dev/docs/filament-nitpick/user-preferences)
- [Creating a theme](https://packstub.dev/docs/filament-nitpick/creating-a-theme)
- [Configuration](https://packstub.dev/docs/filament-nitpick/configuration)

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](https://github.com/packstub/filament-nitpick/blob/main/CHANGELOG.md).

## Security

If you discover a security issue, email [support@packstub.dev](mailto:support@packstub.dev) instead of using the issue tracker.

## Credits

- [Ion Caliman](https://github.com/icaliman)
- [All contributors](https://github.com/packstub/filament-nitpick/contributors)

## License

MIT. See [LICENSE.md](https://github.com/packstub/filament-nitpick/blob/main/LICENSE.md).
