# Creating a theme

A theme is a class implementing `Packstub\Themes\Contracts\Theme`. Extend `Packstub\Themes\Theme` for sensible defaults and you need three methods. For a theme that needs no file of its own, [`Theme::make()`](#a-theme-without-a-class-file) defines one inline.

## A palette-only theme

```php
namespace App\Themes;

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
        return [
            'primary' => Color::Teal,
            'gray' => Color::Slate,
            'info' => Color::Sky,
        ];
    }
}
```

- The **id** is a slug: lowercase letters, digits and dashes. It becomes the stored preference, the asset id and the `data-packstub-theme` value.
- **Colours** take the same shape as `Panel::colors()`: a `Color::*` constant, a hex string, or a full shade array. Slots you leave out keep Filament's defaults.
- Swatches for the picker are derived from `primary`, `info` and `success`; override `getSwatches()` to return up to three CSS colours of your own.

Register it and it is available to every panel:

```php
use App\Themes\Ocean;
use Packstub\Themes\Facades\Themes;

// AppServiceProvider::boot()
Themes::register(Ocean::class);
```

![A products table in Ocean, a palette-only theme: teal accents on Filament's standard surfaces](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/custom-theme.png)

## Adding a stylesheet

A stylesheet turns a palette into a look: surfaces, radii, shadows, the sidebar, typography. Return its absolute path and, if the design leans one way, the mode it is made for:

```php
use Filament\Enums\ThemeMode;

public function getStylesheetPath(): ?string
{
    return resource_path('css/themes/ocean.css');
}

public function getPreferredMode(): ?ThemeMode
{
    return ThemeMode::Light;
}
```

The file is registered as a Filament CSS asset loaded on request, so `php artisan filament:assets` publishes it to `public/css/packstub/filament-themes/packstub-theme-ocean.css` and the plugin links it only while the theme is active.

### Writing the CSS

Scope every rule to the theme's attribute on `<html>`. The plugin sets it before the body paints, and other panels or themes never see your rules:

```css
[data-packstub-theme="ocean"] {
    --pst-radius: 0.75rem;
}

[data-packstub-theme="ocean"] .fi-section,
[data-packstub-theme="ocean"] .fi-ta-ctn {
    border-radius: var(--pst-radius);
}

/* Dark mode: Filament adds .dark to <html> */
.dark[data-packstub-theme="ocean"] .fi-body {
    background: #04121a;
}
```

Useful, stable hooks in Filament 5:

| Hook | What it is |
| --- | --- |
| `.fi-body`, `.fi-topbar`, `.fi-sidebar`, `.fi-main` | The layout regions |
| `.fi-sidebar-open` | Present on the sidebar while it is expanded; `.fi-sidebar:not(.fi-sidebar-open)` is the collapsed state |
| `.fi-sidebar-item`, `.fi-sidebar-item.fi-active`, `.fi-sidebar-item-btn` | Navigation items |
| `.fi-section`, `.fi-ta-ctn`, `.fi-wi-widget`, `.fi-wi-stats-overview-stat`, `.fi-modal-window` | Cards and containers |
| `.fi-btn`, `.fi-btn.fi-color-primary`, `.fi-badge`, `.fi-input-wrp`, `.fi-select-input` | Controls; colour classes are `fi-color-<slot>` |
| `.fi-no-notification`, `.fi-dropdown-panel` | Notifications and dropdowns |
| `--primary-500`, `--gray-200`, … | Filament's palette variables, in OKLCH, following the active theme |

Prefix your own variables (`--ocean-…`) so they never collide with Filament's.

## A theme without a class file

A small theme does not need a file of its own. Define it where you register it, in either of two ways. Both give the same result as a class: the theme is offered in the picker, saved per user and previewed live.

### `Theme::make()`

`Theme::make()` takes the id and returns a theme you describe with fluent methods:

```php
use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Themes\Facades\Themes;
use Packstub\Themes\Theme;

// AppServiceProvider::boot()
Themes::register(
    Theme::make('ocean')->colors(['primary' => Color::Teal]),

    Theme::make('nord')
        ->label('Nord')
        ->description('Arctic blues on calm, dark surfaces.')
        ->colors(['primary' => Color::Sky, 'gray' => Color::Slate])
        ->stylesheet(resource_path('css/themes/nord.css'))
        ->mode(ThemeMode::Dark),
);
```

| Method | What it sets |
| --- | --- |
| `label(string)` | The name in the picker. Left out, it is the id in headline case (`deep-forest` becomes "Deep Forest") |
| `description(?string)` | The line under the name in the picker |
| `colors(array)` | The palette, in the shape of `Panel::colors()` |
| `stylesheet(?string)` | Absolute path to a CSS file, linked while the theme is active |
| `mode(?ThemeMode)` | The light/dark mode the theme is designed for |
| `swatches(array)` | Up to three CSS colours for the picker, instead of the ones derived from the palette |

This is also a short way to offer a stylesheet you already have, such as a compiled CSS file from an installed package: point `stylesheet()` at the file, give the theme its palette, and run `php artisan filament:assets`.

### An anonymous class

`Themes::register()` accepts an instance as well as a class name, so a theme that needs logic (a palette read from config, a label that is translated) can be an anonymous class with the same methods a named class has:

```php
use Filament\Support\Colors\Color;
use Packstub\Themes\Facades\Themes;
use Packstub\Themes\Theme;

Themes::register(new class extends Theme
{
    public function getId(): string
    {
        return 'sunset';
    }

    public function getLabel(): string
    {
        return __('themes.sunset');
    }

    public function getColors(): array
    {
        return ['primary' => config('brand.primary', Color::Orange)];
    }
});
```

Reach for a named class when the theme is shared between apps or shipped as a package, so others can refer to it by class name.

## Shipping a theme as a package

A theme package is a Composer package with the class, the stylesheet and a service provider that registers it:

```php
namespace Vendor\OceanTheme;

use Packstub\Themes\Facades\Themes;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OceanThemeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('ocean-theme');
    }

    public function packageBooted(): void
    {
        Themes::register(Ocean::class);
    }
}
```

Require `packstub/filament-themes` in the package's `composer.json`. Anyone who installs it, runs `filament:assets` and lists the theme on a panel (or leaves the panel open to every registered theme) can use it straight away.

Continue with [Configuration](configuration.md).
