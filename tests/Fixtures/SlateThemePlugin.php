<?php

namespace Packstub\Themes\Tests\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;

/**
 * A theme plugin the way the Filament plugin directory ships them: it gives
 * the panel a palette and several CSS files (plus things a theme never needs:
 * a script, a remote stylesheet), and would restyle every user's panel.
 */
class SlateThemePlugin implements Plugin
{
    public static bool $booted = false;

    public static function make(): static
    {
        return new static;
    }

    public static function path(string $file): string
    {
        return __DIR__.'/css/'.$file;
    }

    public function getId(): string
    {
        return 'slate-theme';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->assets([
                Css::make('slate-theme', static::path('slate.css')),
                Js::make('slate-script', __DIR__.'/slate.js'),
                Css::make('slate-fonts', 'https://fonts.example.com/slate.css'),
            ], 'vendor/slate-theme')
            ->assets([Css::make('slate-corners', static::path('slate-corners.css'))], 'vendor/slate-theme')
            ->colors(['primary' => Color::Slate, 'gray' => Color::Zinc]);
    }

    public function boot(Panel $panel): void
    {
        static::$booted = true;
    }
}
