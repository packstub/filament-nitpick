<?php

namespace Packstub\Nitpick\Themes;

use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Contracts\HasPreview;
use Packstub\Nitpick\Theme;

/**
 * Iridescent glass surfaces and a floating dock-style sidebar. Light-first
 * ("Daybreak") with a full dark variant ("Twilight").
 */
class Aurora extends Theme implements HasPreview
{
    public function getId(): string
    {
        return 'aurora';
    }

    public function getLabel(): string
    {
        return __('nitpick::themes.themes.aurora.label');
    }

    public function getDescription(): ?string
    {
        return __('nitpick::themes.themes.aurora.description');
    }

    public function getColors(): array
    {
        return [
            'primary' => Color::Sky,
            'gray' => Color::Slate,
            'info' => Color::Cyan,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
            'danger' => Color::Rose,
        ];
    }

    public function getPreferredMode(): ?ThemeMode
    {
        return ThemeMode::Light;
    }

    public function getStylesheets(): array
    {
        return [__DIR__.'/../../resources/css/themes/aurora.css'];
    }

    public function getSwatches(): array
    {
        return ['#0ea5e9', '#22d3ee', '#f9a8d4'];
    }

    /**
     * @return array{light?: array<string, string>, dark?: array<string, string>}
     */
    public function getPreview(): array
    {
        return [
            'light' => [
                'background' => 'linear-gradient(135deg, #cdeafe 0%, #e3f8ff 50%, #fde2f1 100%)',
                'sidebar' => 'rgba(255, 255, 255, 0.75)',
                'surface' => 'rgba(255, 255, 255, 0.9)',
                'border' => 'rgba(255, 255, 255, 0.95)',
                'primary' => 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                'text' => '#0e1b2c',
            ],
            'dark' => [
                'background' => 'linear-gradient(135deg, #0b2033 0%, #07101e 50%, #1d0f24 100%)',
                'sidebar' => 'rgba(15, 28, 45, 0.7)',
                'surface' => 'rgba(15, 28, 45, 0.85)',
                'border' => 'rgba(255, 255, 255, 0.08)',
                'primary' => 'linear-gradient(135deg, #38bdf8, #22d3ee)',
                'text' => '#d6eaf7',
            ],
        ];
    }
}
