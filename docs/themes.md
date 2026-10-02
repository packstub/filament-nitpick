# Themes

## The bundled themes

| Id | Theme | Feel | Preferred mode |
| --- | --- | --- | --- |
| `aurora` | Aurora | Iridescent glass surfaces, a floating dock-style sidebar, sky and cyan accents with a blush edge. "Daybreak" in light mode, "Twilight" in dark. | Light |
| `midnight` | Midnight | Deep near-black surfaces with a violet-to-cyan accent gradient on primary actions and stat values. In light mode it keeps the palette and shapes on Filament's standard surfaces. | Dark |

![A Filament dashboard in the Aurora theme: glass cards over a soft sky-to-blush backdrop, a floating dock-style sidebar, gradient stat values](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/aurora.png)

![The same dashboard in the Midnight theme: deep near-black surfaces, violet-to-cyan stat values](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/midnight.png)

Both are ordinary theme classes, `Packstub\Themes\Themes\Aurora` and `Packstub\Themes\Themes\Midnight`, and can be referenced by class or id.

Each covers both modes. Aurora in dark mode, and Midnight on a form:

![A deals table in Aurora's dark variant, "Twilight": the same glass surfaces and dock sidebar on a deep night backdrop](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/aurora-dark.png)

![An edit form in the Midnight theme: dark inputs and a violet-to-cyan gradient on the primary button](https://raw.githubusercontent.com/packstub/art/main/filament-themes/docs/midnight-form.png)

### Preferred mode

A theme declares the light/dark mode it is designed for. While the theme is active, that mode becomes the panel's default: the one a visitor sees before they ever touch the toggle. A user who has toggled light or dark keeps their choice across themes and page loads; the plugin never overwrites it on load. When a user *picks* a theme in the Appearance page, the picker adopts that theme's preferred mode once, because that is a deliberate choice.

`->forceMode()` on the plugin, or `force_mode` in the config, replaces the theme's preference for the whole panel.

## Offering themes on a panel

By default a panel offers every registered theme. Narrow it:

```php
use Packstub\Themes\Themes\Aurora;
use Packstub\Themes\Themes\Midnight;

ThemesPlugin::make()
    ->themes([Aurora::class, Midnight::class, 'ocean'])
```

Entries may be class names, ids or instances. The list can be a closure, evaluated per request. A theme that is registered but not listed cannot be picked or set on that panel.

## The default

```php
ThemesPlugin::make()->default(Midnight::class)
```

Resolution order: the plugin's `->default()`, then the `default` config key, then the first offered theme. A `->default()` that names a theme the panel does not offer throws, so a typo is caught at boot rather than showing the wrong look.

### Following the tenant

The default may be a closure. It runs when the panel boots on each request, so it can read the current tenant, the user's team or anything else in scope:

```php
use Filament\Facades\Filament;

ThemesPlugin::make()
    ->default(fn (): string => Filament::getTenant()?->theme ?? 'aurora')
```

A user's own choice still wins over the tenant default when user selection is on.

## One theme, several panels

Each panel registers its own plugin instance, with its own offer and default:

```php
// Admin panel
->plugin(ThemesPlugin::make()->default(Midnight::class)->userSelectable())

// Customer panel
->plugin(ThemesPlugin::make()->themes([Aurora::class])->default(Aurora::class))
```

A user who chose Aurora in the admin panel is unaffected in the customer panel: choices are stored per panel.

Continue with [User preferences](user-preferences.md).
