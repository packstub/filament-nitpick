<?php

namespace Packstub\Nitpick\Tests\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

/**
 * A theme plugin that registers for the whole app from register(), the way
 * Qt5 and Press do: a stylesheet and a script loaded on every page, and a
 * render hook that is not scoped to its panel.
 */
class GlobalThemePlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'global-theme';
    }

    public function register(Panel $panel): void
    {
        FilamentAsset::register([
            Css::make('global-theme', __DIR__.'/css/slate.css'),
            Js::make('global-theme-rail', __DIR__.'/css/slate.css'),
        ], 'acme/global-theme');

        FilamentView::registerRenderHook(PanelsRenderHook::HEAD_END, fn (): string => '<link id="global-theme-font">');
    }

    public function boot(Panel $panel): void {}
}
