<?php

namespace Packstub\Themes\Themes;

use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Themes\Theme;

/**
 * Iridescent glass surfaces and a floating dock-style sidebar. Light-first
 * ("Daybreak") with a full dark variant ("Twilight").
 */
class Aurora extends Theme
{
    public function getId(): string
    {
        return 'aurora';
    }

    public function getLabel(): string
    {
        return __('packstub-themes::themes.themes.aurora.label');
    }

    public function getDescription(): ?string
    {
        return __('packstub-themes::themes.themes.aurora.description');
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

    public function getStylesheetPath(): ?string
    {
        return __DIR__.'/../../resources/css/themes/aurora.css';
    }

    public function getSwatches(): array
    {
        return ['#0ea5e9', '#22d3ee', '#f9a8d4'];
    }
}
