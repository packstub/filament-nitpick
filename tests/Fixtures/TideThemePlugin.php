<?php

namespace Packstub\Themes\Tests\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

/**
 * A theme plugin built through the app's Vite build: it sets a Vite theme
 * on the panel, registers a palette for the whole app, and lists its
 * Tailwind source as a CSS asset.
 */
class TideThemePlugin implements Plugin
{
    public const ENTRY = 'vendor/acme/tide-theme/resources/css/theme.css';

    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'tide-theme';
    }

    public function register(Panel $panel): void
    {
        FilamentColor::register(['gray' => Color::Stone]);

        $panel
            ->assets([Css::make('tide-source', __DIR__.'/css/tide-source.css')], 'acme/tide-theme')
            ->colors(['primary' => Color::Cyan])
            ->viteTheme(static::ENTRY);
    }

    public function boot(Panel $panel): void {}
}
