<?php

namespace Packstub\Themes\Themes;

use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Themes\Theme;

/**
 * Dark-first with violet-to-cyan accent gradients. In light mode it keeps
 * the palette and shapes on Filament's standard light surfaces.
 */
class Midnight extends Theme
{
    public function getId(): string
    {
        return 'midnight';
    }

    public function getLabel(): string
    {
        return __('packstub-themes::themes.themes.midnight.label');
    }

    public function getDescription(): ?string
    {
        return __('packstub-themes::themes.themes.midnight.description');
    }

    public function getColors(): array
    {
        return [
            'primary' => Color::Violet,
            'gray' => Color::Zinc,
            'info' => Color::Cyan,
            'success' => Color::Teal,
            'warning' => Color::Yellow,
            'danger' => Color::Pink,
        ];
    }

    public function getPreferredMode(): ?ThemeMode
    {
        return ThemeMode::Dark;
    }

    public function getStylesheetPath(): ?string
    {
        return __DIR__.'/../../resources/css/themes/midnight.css';
    }

    public function getSwatches(): array
    {
        return ['#8b5cf6', '#06b6d4', '#0b1020'];
    }
}
