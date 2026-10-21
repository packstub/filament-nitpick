<?php

namespace Packstub\Nitpick\Support;

use Filament\Panel;
use Filament\Support\Assets\Asset;

/**
 * A panel that is never registered or served: it only lets PluginCapture
 * read the assets a theme set on it.
 */
class CapturingPanel extends Panel
{
    /**
     * @return array<string, array<int, Asset>>
     */
    public function getCapturedAssets(): array
    {
        return $this->assets;
    }
}
