<?php

namespace Packstub\Themes\Support;

use Filament\Panel;
use Filament\Support\Assets\Asset;

/**
 * A panel that is never registered or served: it only lets PluginCapture
 * read the assets a plugin set on it.
 */
class CapturingPanel extends Panel
{
    /**
     * @return array<int, Asset>
     */
    public function getCapturedAssets(): array
    {
        return array_merge(...array_values($this->assets), ...[[]]);
    }
}
