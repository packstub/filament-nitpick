<?php

namespace Packstub\Nitpick\Contracts;

use Filament\Panel;

/**
 * A theme that configures the panel while it is the active theme: top
 * navigation, navigation items, fonts, render hooks, a styling plugin.
 * Implement it on a theme class; Theme::make()->panel() does it inline.
 *
 * configurePanel() runs once per request, for the active theme only, when
 * Filament serves the request. Render hooks, assets and colours set there
 * are registered, and plugins added there are booted. Pages, resources and
 * routes cannot be added: routes exist before the request.
 */
interface ConfiguresPanel
{
    public function configurePanel(Panel $panel): void;
}
