<?php

namespace Packstub\Nitpick\Support;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Packstub\Nitpick\ThemeManager;

/**
 * Reads what a Filament theme plugin would give a panel without adding it to
 * one: the plugin registers on a scratch panel that is never served, and the
 * colours, the CSS assets and the Vite theme it set there are taken from it,
 * along with any palette it registered for the whole app. The plugin's boot()
 * never runs, so nothing it does reaches the real panels.
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

        $panel = CapturingPanel::make()->id('nitpick-capture');

        // A plugin may register colours for the whole app: they are read from a stand-in manager.
        $manager = app(ColorManager::class);
        $colors = new CapturingColorManager;

        static::useColorManager($colors);

        try {
            $plugin->register($panel);
        } finally {
            static::useColorManager($manager);
        }

        $stylesheets = [];

        // A Vite theme is the plugin's base stylesheet: it loads first, from the app's own build.
        foreach (Arr::wrap($panel->getViteTheme()) as $entry) {
            $stylesheets[] = ThemeManager::VITE_PREFIX.$entry;
        }

        foreach ($panel->getCapturedAssets() as $asset) {
            if (! $asset instanceof Css || ! is_string($path = $asset->getPath())) {
                continue;
            }

            if ($asset->isRemote() || ! static::needsCompiling($path)) {
                $stylesheets[] = $path;
            }
        }

        return ['colors' => [...$colors->getRegistered(), ...$panel->getColors()], 'stylesheets' => $stylesheets];
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

    protected static function useColorManager(ColorManager $manager): void
    {
        app()->instance(ColorManager::class, $manager);

        FilamentColor::clearResolvedInstance(ColorManager::class);
    }
}
