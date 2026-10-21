<?php

namespace Packstub\Nitpick\Support;

use Closure;
use Filament\Panel;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;

/**
 * Reads what a theme does to its panel (adds a plugin, sets colours) without
 * touching a real one: the theme configures a scratch panel that is never
 * served. Two things are read there, and only there: the colours, for the
 * picker's swatches, and the assets, for `php artisan filament:assets` to
 * publish. The real panel gets the theme's plugin only while the theme is
 * active (PanelServing).
 */
class PluginCapture
{
    /**
     * The colours $configure sets, on the panel or for the whole app, so the
     * picker has a theme's swatches before it is ever active.
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    public static function colorsFrom(Closure $configure): array
    {
        [$panel, $registered] = static::run($configure);

        return [...$registered, ...$panel->getColors()];
    }

    /**
     * Registers the assets $configure adds, on the panel or for the whole
     * app, loaded on request only: `php artisan filament:assets` publishes
     * them, and they load where the theme's plugin asks for them once it is
     * on the real panel. Copies: the plugin may keep its asset objects and
     * register them again there.
     */
    public static function publishAssetsFrom(Closure $configure): void
    {
        [$panel, , $global] = static::run($configure);

        foreach ([$panel->getCapturedAssets(), $global] as $packages) {
            foreach ($packages as $package => $assets) {
                FilamentAsset::register(array_map(fn (Asset $asset): Asset => (clone $asset)->loadedOnRequest(), $assets), $package);
            }
        }
    }

    /**
     * Runs $configure on a scratch panel with stand-ins for Filament's
     * colour, asset and view managers, so nothing it registers for the whole
     * app reaches the real panels: palettes and assets are returned, render
     * hooks are dropped.
     *
     * Filament's facades also replay each registration on every manager the
     * container builds later (a queue worker builds new ones between jobs):
     * those replays are taken back out too.
     *
     * @return array{0: CapturingPanel, 1: array<string, array<int | string, string | int> | string>, 2: array<string, array<int, Asset>>}
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

        return [$panel, $colors->getRegistered(), $assets->getRegistered()];
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
}
