<?php

namespace Packstub\Nitpick\Support;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Theme as FilamentTheme;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Packstub\Nitpick\ThemeManager;

/**
 * Reads what a Filament theme plugin would give a panel without adding it to
 * one: the plugin registers on a scratch panel that is never served, and the
 * colours, the CSS assets and the Vite theme it set there are taken from it,
 * along with the palette and stylesheets it registered for the whole app.
 * The plugin's boot() never runs, and what it registers for the whole app
 * stays here, so nothing it does reaches the real panels.
 */
class PluginCapture
{
    /**
     * @return array{colors: array<string, array<int | string, string | int> | string>, stylesheets: array<int, string>}
     */
    public static function of(mixed $plugin): array
    {
        if (! $plugin instanceof Plugin) {
            $given = is_object($plugin) ? $plugin::class : gettype($plugin);

            throw new InvalidArgumentException("Theme::make()->plugin() expects a Filament plugin, [{$given}] given.");
        }

        [$panel, $registered, $globalAssets] = static::run(fn (Panel $panel) => $plugin->register($panel));

        $stylesheets = [];

        // A Vite theme is the plugin's base stylesheet: it loads first, from the app's own build.
        foreach (Arr::wrap($panel->getViteTheme()) as $entry) {
            $stylesheets[] = ThemeManager::VITE_PREFIX.$entry;
        }

        foreach ([...$panel->getCapturedAssets(), ...$globalAssets] as $asset) {
            // A Filament theme (`$panel->theme()`) or an asset kept for on-request use is not a stylesheet of its own.
            if (! $asset instanceof Css || $asset instanceof FilamentTheme || $asset->isLoadedOnRequest() || ! is_string($path = $asset->getPath())) {
                continue;
            }

            if ($asset->isRemote() || ! static::needsCompiling($path)) {
                $stylesheets[] = $path;
            }
        }

        return ['colors' => [...$registered, ...$panel->getColors()], 'stylesheets' => $stylesheets];
    }

    /**
     * The colours a theme's serving() callback sets, read the same way from
     * a scratch panel, so the picker has its swatches before any request.
     * Only colours: the stylesheets the callback brings are linked by the
     * plugins it adds, on the real panel.
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    public static function colorsFrom(Closure $configure): array
    {
        [$panel, $registered] = static::run($configure);

        return [...$registered, ...$panel->getColors()];
    }

    /**
     * Runs $configure on a scratch panel with stand-ins for Filament's
     * colour, asset and view managers, so nothing it registers for the whole
     * app reaches the real panels: palettes and assets are returned, render
     * hooks are dropped. The assets go back to the real manager loaded on
     * request only, so `php artisan filament:assets` still publishes them.
     *
     * Filament's facades also replay each registration on every manager the
     * container builds later (a queue worker builds new ones between jobs):
     * those replays are taken back out too.
     *
     * @return array{0: CapturingPanel, 1: array<string, array<int | string, string | int> | string>, 2: array<int, Asset>}
     */
    protected static function run(Closure $configure): array
    {
        $panel = CapturingPanel::make()->id('nitpick-capture');

        $real = [ColorManager::class => app(ColorManager::class), AssetManager::class => app(AssetManager::class), ViewManager::class => app(ViewManager::class)];
        $replays = static::replays(array_keys($real));
        $colors = new CapturingColorManager;
        $assets = new CapturingAssetManager;

        static::swap([ColorManager::class => $colors, AssetManager::class => $assets, ViewManager::class => new ViewManager]);

        try {
            $configure($panel);
        } finally {
            static::swap($real);
            static::restoreReplays($replays);
        }

        $globalAssets = [];

        foreach ($assets->getRegistered() as $package => $registered) {
            $globalAssets = [...$globalAssets, ...$registered];

            // Copies: the plugin may keep its asset objects and register them again on a real panel.
            FilamentAsset::register(array_map(fn (Asset $asset): Asset => (clone $asset)->loadedOnRequest(), $registered), $package);
        }

        return [$panel, $colors->getRegistered(), $globalAssets];
    }

    /**
     * @param  array<class-string, object>  $instances
     */
    protected static function swap(array $instances): void
    {
        foreach ($instances as $abstract => $instance) {
            app()->instance($abstract, $instance);
        }

        FilamentColor::clearResolvedInstance(ColorManager::class);
        FilamentAsset::clearResolvedInstance(AssetManager::class);
        FilamentView::clearResolvedInstance(ViewManager::class);
    }

    /**
     * The container's after-resolving callbacks for these classes, which is
     * where Filament's facades keep their replays.
     *
     * @param  array<int, class-string>  $abstracts
     * @return array<class-string, array<int, Closure>>
     */
    protected static function replays(array $abstracts): array
    {
        return (fn (): array => array_map(fn (string $abstract): array => $this->afterResolvingCallbacks[$abstract] ?? [], array_combine($abstracts, $abstracts)))->call(app());
    }

    /**
     * @param  array<class-string, array<int, Closure>>  $replays
     */
    protected static function restoreReplays(array $replays): void
    {
        (function () use ($replays): void {
            foreach ($replays as $abstract => $callbacks) {
                $this->afterResolvingCallbacks[$abstract] = $callbacks;
            }
        })->call(app());
    }

    /**
     * Tailwind source (@apply and friends) does nothing in a browser as it
     * is: such a file is left out, to be compiled by the app's Vite build
     * and added to the theme as a `vite:` stylesheet.
     */
    protected static function needsCompiling(string $path): bool
    {
        return is_file($path) && preg_match('/@(apply|tailwind|source|plugin|config)\b/', (string) file_get_contents($path)) === 1;
    }
}
