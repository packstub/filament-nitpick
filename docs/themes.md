# Themes

## The bundled themes

| Id | Theme | Feel | Preferred mode |
| --- | --- | --- | --- |
| `aurora` | Aurora | Iridescent glass surfaces, a floating dock-style sidebar, sky and cyan accents with a blush edge. "Daybreak" in light mode, "Twilight" in dark. | Light |
| `midnight` | Midnight | Deep near-black surfaces with a violet-to-cyan accent gradient on primary actions and stat values. In light mode it keeps the palette and shapes on Filament's standard surfaces. | Dark |

![A Filament dashboard in the Aurora theme: glass cards over a soft sky-to-blush backdrop, a floating dock-style sidebar, gradient stat values](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/aurora.png)

![The same dashboard in the Midnight theme: deep near-black surfaces, violet-to-cyan stat values](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/midnight.png)

Both are ordinary theme classes, `Packstub\Nitpick\Themes\Aurora` and `Packstub\Nitpick\Themes\Midnight`, and can be referenced by class or id.

Each covers both modes. Aurora in dark mode, and Midnight on a form:

![A deals table in Aurora's dark variant, "Twilight": the same glass surfaces and dock sidebar on a deep night backdrop](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/aurora-dark.png)

![An edit form in the Midnight theme: dark inputs and a violet-to-cyan gradient on the primary button](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/midnight-form.png)

### Preferred mode

A theme declares the light/dark mode it is designed for. While the theme is active, that mode becomes the panel's default: the one a visitor sees before they ever touch the toggle. A user who has toggled light or dark keeps their choice across themes and page loads; the plugin never overwrites it on load. When a user *picks* a theme in the Appearance page, the picker adopts that theme's preferred mode once, because that is a deliberate choice.

`->forceMode()` on the plugin, or `force_mode` in the config, replaces the theme's preference for the whole panel.

## Themes from the Filament plugin directory

The [Filament plugin directory](https://filamentphp.com/plugins) has a growing shelf of themes, free and paid, each with a character of its own. A theme plugin that styles a panel with a palette and CSS files can sit in the picker beside the bundled ones, so a panel offers as many looks as you care to install.

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

Run `php artisan filament:assets` and the theme is registered like any other: a panel can make it the default, offer it to users, and preview it live in the picker.

- The palette and every stylesheet the plugin would give a panel are read from it. There is nothing to copy or merge, and an update of the theme's package is picked up the next time assets are published or built.
- The theme's own plugin stays off the panel: it would restyle the panel for everyone, and here it is one choice among several.
- Configure the plugin the way its docs say, inside the closure (`NoreviqThemePlugin::make()->corners(...)`), and the theme follows.
- Register as many as you like, one `Theme::make()` each, and add `->label()` or `->description()` for the text on its card.

### Example: installing an open-source theme

[Noreviq](https://filamentphp.com/plugins/yousef-al-jallad-noreviq-theme), a teal and graphite theme by Yousef Al-Jallad, ships ready-made CSS, so it takes three steps.

1. Require the package:

    ```bash
    composer require noreviq/filament-theme
    ```

2. Register it as a theme, in `AppServiceProvider::boot()`:

    ```php
    use Noreviq\FilamentTheme\NoreviqThemePlugin;
    use Packstub\Nitpick\Facades\Themes;
    use Packstub\Nitpick\Theme;

    Themes::register(
        Theme::make('noreviq')
            ->description('Teal and graphite with sharp corners.')
            ->plugin(fn () => NoreviqThemePlugin::make()),
    );
    ```

3. Publish the assets:

    ```bash
    php artisan filament:assets
    ```

Open the Appearance page: Noreviq is a card beside Aurora and Midnight. To make it the look everyone starts with, set `->default('noreviq')` on the plugin.

### Themes that build with Vite

Some themes are Tailwind source, compiled by your app's Vite build. Follow the build step from the theme's own docs, and the stylesheet is linked from the build while the theme is active.

**A theme that sets a Vite theme on its panel.** [Abyss](https://filamentphp.com/plugins/osama-nagi-abyss-theme), by Osama Nagi, asks for its stylesheet in `vite.config.js`:

```js
// vite.config.js
laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'vendor/osamanagi/filament-abyss-theme/resources/css/theme.css',
    ],
}),
```

```bash
composer require osamanagi/filament-abyss-theme
npm run build
```

```php
use Nagi\FilamentAbyssTheme\FilamentAbyssThemePlugin;

Themes::register(
    Theme::make('abyss')->plugin(fn () => FilamentAbyssThemePlugin::make()),
);
```

The Vite theme the plugin would set on a panel is read from it along with its palette, so that is all.

**A theme imported into a Filament theme file.** [Nord](https://filamentphp.com/plugins/andreia-bohner-nord-theme), by Andréia Bohner, asks for an `@import` in a custom Filament theme. Give it a file of its own, so it is compiled apart from your panel's stylesheet:

```css
/* resources/css/themes/nord.css */
@import '../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../vendor/andreia/filament-nord-theme/resources/css/theme.css';
```

Add `resources/css/themes/nord.css` to the `input` array in `vite.config.js`, run `npm run build`, and name the entry on the theme with the `vite:` prefix:

```php
use Andreia\FilamentNordTheme\FilamentNordThemePlugin;

Themes::register(
    Theme::make('nord')
        ->plugin(fn () => FilamentNordThemePlugin::make())
        ->stylesheet('vite:resources/css/themes/nord.css'),
);
```

The plugin supplies the palette; the compiled file supplies the look. A `vite:` stylesheet works for your own Tailwind-built theme too.

### A theme without a plugin class

A theme that is a palette, or a palette and a few rules, needs no plugin to read from. [Shadcn](https://filamentphp.com/plugins/openplain-shadcn-theme), by Openplain, is a set of colour constants:

```php
use Openplain\FilamentShadcnTheme\Color as ShadcnColor;

Themes::register(
    Theme::make('shadcn')->colors(['primary' => ShadcnColor::Default]),
);
```

A package like this may style every panel from its service provider. To keep those rules to its own theme, save them as a CSS file, pass it to `->stylesheet()`, and list the package under `extra.laravel.dont-discover` in `composer.json`.

## Offering themes on a panel

By default a panel offers every registered theme. Narrow it:

```php
use Packstub\Nitpick\Themes\Aurora;
use Packstub\Nitpick\Themes\Midnight;

NitpickPlugin::make()
    ->themes([Aurora::class, Midnight::class, 'ocean'])
```

Entries may be class names, ids or instances. The list can be a closure, evaluated per request. A theme that is registered but not listed cannot be picked or set on that panel.

## The default

```php
NitpickPlugin::make()->default(Midnight::class)
```

Resolution order: the plugin's `->default()`, then the `default` config key, then the first offered theme. A `->default()` that names a theme the panel does not offer throws, so a typo is caught at boot rather than showing the wrong look.

### Following the tenant

The default may be a closure. It runs when the panel boots on each request, so it can read the current tenant, the user's team or anything else in scope:

```php
use Filament\Facades\Filament;

NitpickPlugin::make()
    ->default(fn (): string => Filament::getTenant()?->theme ?? 'aurora')
```

A user's own choice still wins over the tenant default when user selection is on.

## One theme, several panels

Each panel registers its own plugin instance, with its own offer and default:

```php
// Admin panel
->plugin(NitpickPlugin::make()->default(Midnight::class)->userSelectable())

// Customer panel
->plugin(NitpickPlugin::make()->themes([Aurora::class])->default(Aurora::class))
```

A user who chose Aurora in the admin panel is unaffected in the customer panel: choices are stored per panel.

Continue with [User preferences](user-preferences.md).
