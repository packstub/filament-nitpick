<?php

namespace Packstub\Nitpick\Support;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\AssetManager;

/**
 * Stands in for Filament's asset manager while PluginCapture configures a
 * scratch panel, so assets a plugin registers for the whole app
 * (FilamentAsset::register()) are read here and never load on every panel.
 */
class CapturingAssetManager extends AssetManager
{
    /** @var array<string, array<int, Asset>> */
    protected array $registered = [];

    public function register(array $assets, string $package = 'app'): void
    {
        $this->registered[$package] = [...($this->registered[$package] ?? []), ...$assets];

        parent::register($assets, $package);
    }

    /**
     * @return array<string, array<int, Asset>>
     */
    public function getRegistered(): array
    {
        return $this->registered;
    }
}
