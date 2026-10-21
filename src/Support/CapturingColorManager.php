<?php

namespace Packstub\Nitpick\Support;

use Filament\Support\Colors\ColorManager;

/**
 * Stands in for Filament's colour manager while PluginCapture configures a
 * scratch panel, so a palette a plugin registers for the whole app
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
