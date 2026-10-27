# For theme authors

Nitpick puts several themes in one panel and lets each person pick theirs. A Filament theme plugin joins the picker with one line in the app:

```php
Themes::register(
    Theme::make('harbor')->plugin(fn () => HarborThemePlugin::make()),
);
```

Nothing in your plugin has to know about Nitpick for this to work. This page covers the few habits that make a theme plugin look its best there, and in any app with more than one panel, plus an optional way to put your theme in the picker as soon as it is installed.

## How Nitpick runs your plugin

- **While your theme is active**, your plugin is added to the panel for that request, the way a panel provider adds it: `register()` runs, then `boot()`. Everything you set on the panel applies, exactly as your docs describe.
- **While another theme is active**, your plugin is not on the panel at all. Nothing it would register runs.
- **For the picker's swatches**, Nitpick calls `register()` once on a scratch panel that is never served, and reads the colours you set there. Anything else registered in that run is set aside.
- **When `php artisan filament:assets` runs** (or `filament:upgrade`, which Composer runs after every update), Nitpick calls `register()` on a scratch panel and hands the assets you added to Filament, so your files are published even though your plugin is not on any panel at that moment.
- **Picking your theme reloads the page**, so the panel is built with your plugin from the start.

## Configure the panel you are given

Set everything on the `$panel` passed to `register()`. It is scoped to that panel, so it comes and goes with your theme, and an app with an admin panel and a customer panel can give each its own look.

```php
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;

class HarborThemePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'harbor-theme';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->colors(['primary' => Color::Sky, 'gray' => Color::Slate])
            ->font('Inter')
            ->assets([
                Css::make('harbor-theme', __DIR__.'/../resources/dist/harbor.css'),
            ], 'acme/harbor-theme')
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): string => view('harbor::sidebar-footer')->render());
    }

    public function boot(Panel $panel): void {}
}
```

| To set | Use | Rather than |
| --- | --- | --- |
| Colours | `$panel->colors()` | `FilamentColor::register()` |
| Stylesheets and scripts | `$panel->assets()` | `FilamentAsset::register()` in a service provider |
| A compiled Filament theme | `$panel->viteTheme()` or `$panel->theme()` | Registering it for every panel |
| Render hooks | `$panel->renderHook()` | `FilamentView::registerRenderHook()` |
| Fonts | `$panel->font()` | A stylesheet that imports the font for every page |
| Layout | `$panel->topNavigation()`, `$panel->sidebarWidth()` and friends | CSS that reshapes every panel |

The methods on the right register for the whole app. From a service provider they run on every request, so the theme would style every panel even when someone has picked another one. From `register()` they work while your theme is active, though they reach every panel of that request. The panel methods keep it to the panel your theme was added to.

## Set the colours in `register()`

The picker shows three swatches per theme, taken from the `primary`, `info` and `success` colours. Nitpick reads them from the scratch panel, where only `register()` runs, so set the palette there rather than in `boot()`. A palette that changes with your plugin's options follows them, because the app's closure configures the plugin before `register()` is called:

```php
Theme::make('harbor-dusk')->plugin(fn () => HarborThemePlugin::make()->variant('dusk')),
```

## Keep `register()` free of the request

The scratch runs (swatches, publishing assets) happen outside a request: there is no signed-in user, no session, and in the publishing run no HTTP request at all. Keep `register()` to configuration, and read the user, the tenant or the request in a closure Filament evaluates later:

```php
$panel->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): string => view('harbor::greeting', [
    'user' => auth()->user(),
])->render());
```

If `register()` throws in a scratch run, Nitpick reports it to the app's log and carries on, without your theme's swatches, or without its files published when it happens in the publishing run.

## Keep view overrides to your panels

A theme that replaces Filament's topbar, sidebar or layout with views of its own usually does it with `View::prependNamespace('filament-panels', ...)`. From a service provider that applies to every panel in the app, and to every theme in the picker, as soon as the package is installed: an app cannot offer your theme as one choice among others, and a second panel cannot opt out. Prepend the namespace from the plugin instead, where it runs only on the panels that use your theme:

```php
use Illuminate\Support\Facades\View;

public function register(Panel $panel): void
{
    View::prependNamespace('filament-panels', __DIR__.'/../resources/views/filament-panels');
}
```

That runs while your theme is active, and only then, so another theme on the same panel gets Filament's views back.

## Ship the files your assets point to

`Css::make('harbor-theme', __DIR__.'/../resources/dist/harbor.css')` is read by `php artisan filament:assets`, which Composer also runs after every update through `filament:upgrade`. If the file is not in the package (a `dist/` folder left out of the release), the command stops for the whole app, with every plugin's assets unpublished, not just yours. Commit the built file, or build it in a release step, and check the tag on Packagist once.

## A custom topbar keeps the action modals

Filament's own topbar view ends with `<x-filament-actions::modals />`. The user menu needs it for any item that opens a modal: Nitpick's Appearance item, and any user-menu action an app adds. If your theme ships its own topbar view, keep that line at the end, and if it ships its own sidebar, keep the sidebar's `z-index` below Filament's modal container (`.fi-modal > .fi-modal-window-ctn` sits at 40), or a modal opened from the user menu is painted under it.

## Leave pages and resources to the panel provider

A theme plugin is added mid-request, after Filament has registered the panel's routes and Livewire components, so pages, resources and widgets cannot join that way. Nitpick takes any that a theme adds back out and reports it. If your plugin also ships a page (a theme settings screen, say), offer a switch to turn it off, and mention it in your docs:

```php
HarborThemePlugin::make()->settingsPage(false)
```

## Ship ready-to-use CSS

A stylesheet in `$panel->assets()` is linked as it is, so ship it compiled. Tailwind source (`@apply`, `@source`, `@tailwind`) only works through the app's own Vite build: if your theme needs one, set it with `$panel->viteTheme()` and document the entry the app adds to `vite.config.js`. Both work in Nitpick without anything extra.

## Optional: join the picker on install

To offer your theme in the picker as soon as both packages are installed, register it from your service provider when Nitpick is present, and suggest Nitpick in `composer.json`:

```php
use Filament\Enums\ThemeMode;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Theme;

public function packageBooted(): void
{
    if (class_exists(Themes::class)) {
        Themes::register(
            Theme::make('harbor')
                ->label('Harbor')
                ->description('Sky and slate, with a calm sidebar.')
                ->mode(ThemeMode::Light)
                ->plugin(fn () => HarborThemePlugin::make()),
        );
    }
}
```

```json
"suggest": {
    "packstub/filament-nitpick": "Offers Harbor as a choice on the Appearance page, beside other themes"
}
```

- Pick an id that is unlikely to clash with another theme's: your theme's name, lowercase with dashes.
- `->label()` and `->description()` are the text on the theme's card; `->mode()` is the light/dark mode the theme is designed for, applied when someone picks it.
- Apps that add your plugin to a panel directly keep working as before. Mention in your docs that, with Nitpick, the plugin goes to the theme rather than to the panel, so it is not on the panel twice.
- An app can narrow the themes a panel offers with `->themes([...])` on `NitpickPlugin`, so registering on install never forces your theme on anyone.

## Check it

In an app with Nitpick installed and `->picker()` on the panel:

1. Register your theme with `Theme::make()->plugin()` and run `php artisan filament:assets`.
2. Open the Appearance page: your theme's card shows its swatches.
3. Pick it: the page reloads in your theme, with your fonts, hooks and layout.
4. Pick Aurora: none of your stylesheets, hooks or layout options are left on the page.
5. Open a second panel that does not offer your theme: it looks the way it did before.
