<?php

namespace Packstub\Nitpick\Tests\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

/**
 * A theme plugin that only works on a real panel: it adds a render hook,
 * and its boot() sets a Filament theme (the way Press does).
 */
class RailThemePlugin implements Plugin
{
    public static bool $booted = false;

    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'rail-theme';
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(PanelsRenderHook::BODY_START, fn (): string => '<div id="rail-theme-marker"></div>');
    }

    public function boot(Panel $panel): void
    {
        static::$booted = true;
    }
}
