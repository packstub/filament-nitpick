<?php

namespace Packstub\Nitpick\Themes;

use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Contracts\HasPreview;
use Packstub\Nitpick\Theme;

/**
 * Dark-first with violet-to-cyan accent gradients. In light mode it keeps
 * the palette and shapes on Filament's standard light surfaces.
 */
class Midnight extends Theme implements HasPreview
{
    public function getId(): string
    {
        return 'midnight';
    }

    public function getLabel(): string
    {
        return __('nitpick::themes.themes.midnight.label');
    }

    public function getDescription(): ?string
    {
        return __('nitpick::themes.themes.midnight.description');
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

    public function getStylesheets(): array
    {
        return [__DIR__.'/../../resources/css/themes/midnight.css'];
    }

    public function getSwatches(): array
    {
        return ['#8b5cf6', '#06b6d4', '#0b1020'];
    }

    /**
     * @return array{light?: array<string, string>, dark?: array<string, string>}
     */
    public function getPreview(): array
    {
        return [
            'light' => [
                'primary' => 'linear-gradient(135deg, #8b5cf6, #6366f1 50%, #06b6d4)',
            ],
            'dark' => [
                'background' => 'radial-gradient(circle at 0% 0%, rgba(139, 92, 246, 0.25), transparent 60%), #060914',
                'sidebar' => 'rgba(11, 16, 32, 0.85)',
                'surface' => '#0b1020',
                'border' => 'rgba(139, 92, 246, 0.25)',
                'primary' => 'linear-gradient(135deg, #8b5cf6, #6366f1 50%, #06b6d4)',
                'text' => '#e2e8f0',
            ],
        ];
    }
}
