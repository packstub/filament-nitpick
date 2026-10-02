<?php

namespace Packstub\Themes\Support;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use InvalidArgumentException;

/**
 * Reads what a Filament theme plugin would give a panel without adding it to
 * one: the plugin registers on a scratch panel that is never served, and the
 * colours and local CSS assets it set there are taken from it. The plugin's
 * boot() never runs, so nothing it does reaches the real panels.
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

            throw new InvalidArgumentException("themePlugin() expects a Filament plugin, [{$given}] given.");
        }

        $panel = CapturingPanel::make()->id('packstub-themes-capture');

        $plugin->register($panel);

        $stylesheets = [];

        foreach ($panel->getCapturedAssets() as $asset) {
            if ($asset instanceof Css && ! $asset->isRemote() && is_string($path = $asset->getPath())) {
                $stylesheets[] = $path;
            }
        }

        return ['colors' => $panel->getColors(), 'stylesheets' => $stylesheets];
    }
}
