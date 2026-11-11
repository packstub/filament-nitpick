<?php

namespace Packstub\Nitpick\Support;

use Filament\Support\Colors\Color;
use Packstub\Nitpick\Contracts\HasPreview;
use Packstub\Nitpick\Contracts\Theme;
use Packstub\Nitpick\ThemeManager;

/**
 * The colours of a theme's preview card, in light and dark: drawn from its
 * palette the way Filament paints a panel, with whatever the theme gives
 * through HasPreview on top. Null for a theme whose colours are unknown (a
 * plugin that sets them in boot()), which keeps its swatches instead.
 */
class ThemePreview
{
    public const PARTS = ['background', 'sidebar', 'surface', 'border', 'primary', 'text'];

    /**
     * @param  array<string, array<int | string, string | int> | string>  $colors  the palette the panel would use
     * @return array{light: array<string, string>, dark: array<string, string>} | null
     */
    public static function for(Theme $theme, array $colors): ?array
    {
        $given = $theme instanceof HasPreview ? $theme->getPreview() : [];

        if (! isset($colors['primary']) && $given === []) {
            return null;
        }

        // Filament's own gray and primary fill any shade a palette leaves out.
        $gray = static::palette($colors['gray'] ?? Color::Zinc) + static::palette(Color::Zinc);
        $primary = static::palette($colors['primary'] ?? Color::Amber) + static::palette(Color::Amber);

        $derived = [
            'light' => [
                'background' => $gray[50],
                'sidebar' => 'oklch(1 0 0)',
                'surface' => 'oklch(1 0 0)',
                'border' => $gray[200],
                'primary' => $primary[600],
                'text' => $gray[950],
            ],
            'dark' => [
                'background' => $gray[950],
                'sidebar' => $gray[900],
                'surface' => $gray[900],
                'border' => $gray[800],
                'primary' => $primary[500],
                'text' => 'oklch(1 0 0)',
            ],
        ];

        foreach (['light', 'dark'] as $mode) {
            $derived[$mode] = [...$derived[$mode], ...array_intersect_key($given[$mode] ?? [], array_flip(static::PARTS))];
        }

        return $derived;
    }

    /**
     * A preview's colours as CSS variables for one mode's half of the card.
     *
     * @param  array<string, string>  $parts
     */
    public static function style(array $parts): string
    {
        return implode(' ', array_map(
            fn (string $part, string $value): string => "--nitpick-preview-{$part}: ".str_replace([';', '{', '}', '<', '>'], '', $value).';',
            array_keys($parts),
            $parts,
        ));
    }

    /**
     * A palette's shades as CSS colours, by shade, aliases resolved the way Filament does.
     *
     * @param  array<int | string, string | int> | string  $color
     * @return array<int | string, string>
     */
    protected static function palette(array|string $color): array
    {
        $shades = [];

        foreach (app(ThemeManager::class)->cssVariables(['c' => $color]) as $name => $value) {
            $shades[substr($name, 2)] = $value;
        }

        return $shades;
    }
}
