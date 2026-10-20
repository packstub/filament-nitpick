# Creating a theme

A theme is a class implementing `Packstub\Nitpick\Contracts\Theme`. Extend `Packstub\Nitpick\Theme` for sensible defaults and you need three methods. For a theme that needs no file of its own, [`Theme::make()`](#a-theme-without-a-class-file) defines one inline.

## A palette-only theme

```php
namespace App\Themes;

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
use Packstub\Nitpick\Facades\Themes;

// AppServiceProvider::boot()
Themes::register(Ocean::class);
```

![A products table in Ocean, a palette-only theme: teal accents on Filament's standard surfaces](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/custom-theme.png)

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

The file is registered as a Filament CSS asset loaded on request, so `php artisan filament:assets` publishes it to `public/css/packstub/filament-nitpick/packstub-theme-ocean.css` and the plugin links it only while the theme is active.

A look split over several files returns them all from `getStylesheets()` instead, in the order they should load. An entry is the absolute path to a CSS file, a URL (linked as it is), or `vite:<entry>` for a file compiled by your Vite build (linked from the build's manifest):

```php
public function getStylesheets(): array
{
    return [
        resource_path('css/themes/ocean.css'),
        resource_path('css/themes/ocean-corners.css'),
    ];
}
```

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
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

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
| `stylesheet(?string)` | A stylesheet linked while the theme is active: the absolute path to a CSS file, a URL, or `vite:<entry>` for an entry of your Vite build |
| `stylesheets(array)` | Several stylesheets, linked in the order given |
| `plugin(Plugin\|Closure)` | A Filament theme plugin whose palette and stylesheets become the theme's own; see [below](#a-theme-from-the-filament-plugin-directory) |
| `mode(?ThemeMode)` | The light/dark mode the theme is designed for |
| `swatches(array)` | Up to three CSS colours for the picker, instead of the ones derived from the palette |

### An anonymous class

`Themes::register()` accepts an instance as well as a class name, so a theme that needs logic (a palette read from config, a label that is translated) can be an anonymous class with the same methods a named class has:

```php
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

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

## A theme from the Filament plugin directory

Many themes in the [Filament plugin directory](https://filamentphp.com/plugins) are plugins of their own: given to a panel with `$panel->plugin()`, they restyle it for everyone. Given to a theme with `Theme::make()->plugin()`, the same plugin becomes one choice in the picker, beside the others. Install the package, then hand its plugin to a theme, configured the way its own docs describe:

```php
use Noreviq\FilamentTheme\NoreviqThemePlugin;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

// AppServiceProvider::boot()
Themes::register(
    Theme::make('noreviq')
        ->description('Teal and graphite with sharp corners.')
        ->plugin(fn () => NoreviqThemePlugin::make()),
);
```

Run `php artisan filament:assets` and the theme is in the picker. Do not add the theme's plugin to the panel as well: the theme takes its place.

What is read from the plugin, without adding it to a panel:

| From the plugin | Becomes |
| --- | --- |
| The colours it sets on its panel, and any palette it registers for the whole app | The theme's palette |
| Its CSS assets, local files and URLs alike, however many | The theme's stylesheets, in the plugin's order |
| A Vite theme it sets on its panel (`->viteTheme()`) | A `vite:` stylesheet, linked from your app's build |

A CSS asset that is Tailwind source (`@apply` and friends) is left out, because a browser cannot use it as it is: compile it in your Vite build and add it with `->stylesheet('vite:...')`. [Themes](themes.md#themes-that-build-with-vite) has a worked example of each case.

`colors()`, `stylesheet()` and `stylesheets()` on the same theme apply on top, for a tweak of your own. A palette the plugin registers for the whole app stays with the theme: it does not reach your other panels or themes.

What carries over is what a theme is made of: colours and stylesheets. Fonts, render hooks and layout options the plugin sets on its panel stay behind. A theme that depends on them can configure the panel itself while it is active: see [Configuring the panel](#configuring-the-panel).

## Configuring the panel

Some looks are more than colours and CSS: navigation along the top, a narrower sidebar, other fonts, a render hook. `->serving()` configures the panel while the theme is active, the way a panel provider would:

```php
use Filament\Panel;

Themes::register(
    Theme::make('newsroom')
        ->colors(['primary' => Color::Red])
        ->stylesheet(resource_path('css/themes/newsroom.css'))
        ->serving(fn (Panel $panel) => $panel
            ->topNavigation()
            ->font('Fraunces')),
);
```

A theme class implements `Packstub\Nitpick\Contracts\ConfiguresPanel` instead:

```php
use Filament\Panel;
use Packstub\Nitpick\Contracts\ConfiguresPanel;

class Newsroom extends Theme implements ConfiguresPanel
{
    // getId(), getLabel(), getColors() ...

    public function servingPanel(Panel $panel): void
    {
        $panel->topNavigation()->font('Fraunces');
    }
}
```

A theme plugin that needs a real panel can be added to it here, so it works exactly as its docs describe, only while its theme is active:

```php
use Elemind\PressFilamentTheme\PressFilamentTheme;

Theme::make('press')->serving(fn (Panel $panel) => $panel->plugin(PressFilamentTheme::make())),
```

How it runs:

- Once per request, for the active theme only, as Filament serves the request. The user's choice is known by then.
- What the panel reads while rendering just works: top navigation, navigation items, sidebar width, fonts, brand, user menu items.
- Render hooks, assets and colours set here are registered, and a plugin added here is booted. The plugin colour overrides (`->colors()` on `NitpickPlugin`) still win.
- Pages, resources and widgets cannot be added: their routes and Livewire components are registered before the request. Any the callback adds are left out, and the reason is reported to your log. A plugin that brings its own pages belongs on the panel itself, or with its pages turned off.
- Picking such a theme on the Appearance page, or leaving one, reloads the page instead of previewing it live, because the layout changes.

The picker shows a theme's swatches before any request, so Nitpick also runs the callback once on a panel that is never served and takes the colours it sets: a plugin added there gives the theme its palette in the picker too. That run has no request and no signed-in user. A callback that needs one is reported to your log, and the theme keeps the colours given to it with `->colors()`.

## Shipping a theme as a package

A theme package is a Composer package with the class, the stylesheet and a service provider that registers it:

```php
namespace Vendor\OceanTheme;

use Packstub\Nitpick\Facades\Themes;
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

Require `packstub/filament-nitpick` in the package's `composer.json`. Anyone who installs it, runs `filament:assets` and lists the theme on a panel (or leaves the panel open to every registered theme) can use it straight away.

Continue with [Configuration](configuration.md).
