<?php

namespace Packstub\Themes\Support;

use Filament\Support\Colors\ColorManager;

/**
 * Stands in for Filament's colour manager while PluginCapture registers a
 * plugin, so a palette the plugin registers for the whole app
 * (FilamentColor::register()) is read here and never reaches the real one.
 */
class CapturingColorManager extends ColorManager
{
    /**
     * @return array<string, array<int | string, string | int> | string>
     */
    public function getRegistered(): array
    {
        $registered = [];

        foreach ($this->colors as $colors) {
            $registered = [...$registered, ...$this->evaluate($colors)];
        }

        return $registered;
    }
}
