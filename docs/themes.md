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

The [Filament plugin directory](https://filamentphp.com/plugins) has a growing shelf of themes, free and paid, each with a character of its own. Any of them can sit in the picker beside the bundled ones, so a panel offers as many looks as you care to install.

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

Run `php artisan filament:assets` and the theme is registered like any other: a panel can make it the default and offer it to users.

- The plugin joins the panel while its theme is active, and only then, so it styles the panel exactly as its own docs describe: palette, stylesheets, fonts, render hooks, layout. While another theme is active, nothing of it is on the page.
- Do not add the plugin to the panel as well: the theme takes its place.
- Configure the plugin the way its docs say, inside the closure (`NoreviqThemePlugin::make()->corners(...)`), and the theme follows.
- The picker shows the theme's swatches from the plugin's colours. Picking it reloads the page, so the panel is built with the plugin from the start.
- Register as many as you like, one `Theme::make()` each, and add `->label()` or `->description()` for the text on its card.

[Tested themes](tested-themes.md) lists the community themes that have been run through the picker, with the setup each one takes.

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

The plugin sets its Vite theme on the panel while it is active, so that is all.

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

**A theme whose install command edits the panel's theme file.** Some themes come with an install command that imports their CSS into the panel's own theme file (`resources/css/filament/admin/theme.css`), so the theme would be compiled into every page of the panel. Skip that step and give the theme a file of its own, as for Nord above: Filament's theme first, then the theme's CSS, listed in `vite.config.js` and named on the theme with the `vite:` prefix. Edinburgh, Inverness, Aberdeen and Rail take this shape.

**A theme whose CSS has no Filament import.** A theme file that starts with `@apply` rules and no `@import` of Filament's theme stops the build with `Cannot apply unknown utility class`, because Tailwind has not seen Filament's utilities yet. The same wrapper file solves it:

```css
/* resources/css/themes/sleek.css */
@import '../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../vendor/hansenmakangiras/filament-sleek-theme/resources/css/theme.css';
```

Pass the wrapper wherever the theme's docs ask for its own file: `->stylesheet('vite:resources/css/themes/sleek.css')`, or the plugin's own option when it has one (`FilamentSleekTheme::make()->theme('resources/css/themes/sleek.css')`).

### A theme whose plugin needs a Vite theme on the panel

A few plugins expect the panel to carry their compiled theme as its `->viteTheme()`, and say so when it is missing: [Kinetics](https://filamentphp.com/plugins/otatechie-kinetics) stops in `boot()` with directions, Arasy lays out the page around it. A stylesheet on the theme is not what they look for; set the Vite theme on the panel with `->panel()`, which runs while the theme is active:

```php
use Filament\Panel;
use Otatechie\Kinetics\KineticsPlugin;

Themes::register(
    Theme::make('kinetics')
        ->plugin(fn () => KineticsPlugin::make()->appearancePage(false))
        ->panel(fn (Panel $panel) => $panel->viteTheme('resources/css/themes/kinetics.css')),
);
```

`resources/css/themes/kinetics.css` is the file the theme's docs tell you to create (its layers, Filament's theme, its CSS), listed in `vite.config.js` as usual. Kinetics also ships an Appearance page of its own, which is turned off here: a page cannot join the panel while a theme is served, and Nitpick's picker is the one users see. A theme that applies itself with a static method instead of a plugin (`FilamentItaliaTheme::applyTo($panel)`) goes in `->panel()` the same way.

### Swatches and labels

The picker shows three swatches per theme, taken from the colours its plugin sets in `register()`. A theme that is CSS only, or that sets its colours in `boot()`, shows no swatches (its card keeps the row's height, so the names line up), and `->colors()` gives it some. The label is optional: left out, it is the id in headline case (`newsroom` reads "Newsroom", `deep-forest` "Deep Forest"), so `->label()` is only needed when that reads wrong, as `meui` does ("Meui" rather than "MeUI"):

```php
Theme::make('meui')
    ->label('MeUI')
    ->colors(['primary' => Color::Teal, 'gray' => Color::Slate])
    ->plugin(fn () => MeUiPlugin::make()),
```

`->colors()` applies on top of the plugin's palette, so pick the colours the theme's CSS is designed around.

### A theme without a plugin class

A theme that is a palette, or a palette and a few rules, needs no plugin to read from. [Shadcn](https://filamentphp.com/plugins/openplain-shadcn-theme), by Openplain, is a set of colour constants:

```php
use Openplain\FilamentShadcnTheme\Color as ShadcnColor;

Themes::register(
    Theme::make('shadcn')->colors(['primary' => ShadcnColor::Default]),
);
```

### Packages that style every panel from their service provider

Most theme packages do everything from their plugin, which Nitpick puts on the panel only while the theme is active. A few register their stylesheet from the service provider instead, as a Filament asset for the whole app, so it loads on every panel and under every theme the moment the package is installed; Openplain's Shadcn and Tailscale are two. The treatment is the same for both: keep the service provider out of package discovery, and give the theme the stylesheet yourself, so it is linked only while the theme is active.

```json
// composer.json
"extra": {
    "laravel": {
        "dont-discover": ["vlados/filament-tailscale-theme"]
    }
}
```

```php
use Vlados\FilamentTailscaleTheme\FilamentTailscaleTheme;

Themes::register(
    Theme::make('tailscale')
        ->plugin(fn () => FilamentTailscaleTheme::make())
        ->stylesheet(base_path('vendor/vlados/filament-tailscale-theme/resources/dist/filament-tailscale-theme.css')),
);
```

Run `composer dump-autoload` after the change. For a package without a plugin class (Shadcn), the theme is `->colors()` and the stylesheet alone.

A package that replaces Filament's views from its service provider (`View::prependNamespace('filament-panels', ...)`, for its own topbar or sidebar) reaches every panel too, and a stylesheet cannot undo that: it works as the one theme of a panel, not as one choice among several. The package can scope it by prepending its views from the plugin instead; [For theme authors](theme-authors.md#keep-view-overrides-to-your-panels) says how.

### A theme that changes the layout

Some theme plugins set more than colours and stylesheets: [Press](https://filamentphp.com/plugins/elemind-press-filament-theme), by Elemind, moves navigation into a top rail and sets a prebuilt Filament theme when its plugin boots. `->plugin()` covers that too, since the plugin is on the panel while its theme is active:

```php
use Elemind\PressFilamentTheme\PressFilamentTheme;

Themes::register(
    Theme::make('press')
        ->description('An editorial look with navigation along the top. Open source, by Elemind.')
        ->plugin(fn () => PressFilamentTheme::make()),
);
```

The same goes for a theme that styles the panel from render hooks, like [DaisyUI Themes](https://filamentphp.com/plugins/osama-nagi-daisyui-themes). To change the panel further while the theme is active (top navigation, a font of your own), add `->panel()`; [Configuring the panel](creating-a-theme.md#configuring-the-panel) has the details, and what cannot be added this way.

Building a theme plugin of your own? [For theme authors](theme-authors.md) has the habits that make it work well here.

### When a theme cannot be served

A theme that configures the panel can fail while doing it: its plugin stops in `boot()` because a step of its setup is missing (Kinetics without its Vite theme), or a `->panel()` callback throws. Rather than turn every page into an error for the person who picked it, the picker's page included, Nitpick reports the failure to your log, serves the panel's default theme for that request, and shows the user a notice that their theme is unavailable, with the picker one click away. Their choice is kept, so the theme comes back on its own once the setup is complete. A default theme that fails still throws: that is the panel's own configuration, and you want to see it.

A theme can also turn on a feature the app is not ready for: Rail's plugin enables database notifications, which need a `notifications` table (`php artisan make:notifications-table`, then `migrate`). That fails later, while the page renders, so Nitpick cannot catch it the same way. Instead, when a request ends in a server error while a user's own theme configured the panel, Nitpick logs a warning and serves that user the panel's default theme for the next 10 minutes, with the same notice, so they can still reach the picker. After that the theme is tried again, and picking a theme tries it at once. An error with another cause sets the theme aside too, for that user only; it costs them their theme for a while, never the panel. Run a theme's setup steps before offering it, and the warning in your log points to the theme when one is missing.

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
->plugin(NitpickPlugin::make()->default(Midnight::class)->picker())

// Customer panel
->plugin(NitpickPlugin::make()->themes([Aurora::class])->default(Aurora::class))
```

A user who chose Aurora in the admin panel is unaffected in the customer panel: choices are stored per panel.

Continue with [User preferences](user-preferences.md).
