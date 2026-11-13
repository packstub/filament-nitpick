<?php

namespace Packstub\Nitpick\Support;

use Filament\Support\Colors\Color;

/**
 * A user's accent colour: one of Filament's palettes by name ("indigo"), or
 * any hex colour ("#4f46e5"), for which Filament generates a palette.
 */
class Accent
{
    /**
     * Filament's colours offered as swatches: the ones that read as an
     * accent, without its grays, and that pass problem() (Yellow does not:
     * its 600 shade is too light on white).
     *
     * @return array<string, array<int, string>>
     */
    public static function swatches(): array
    {
        $colors = array_diff_key(Color::all(), array_flip(['slate', 'gray', 'zinc', 'neutral', 'stone', 'mauve', 'olive', 'mist', 'taupe']));

        return array_filter($colors, fn (string $name): bool => static::problem($name) === null, ARRAY_FILTER_USE_KEY);
    }

    /**
     * The palette for the panel's `primary` slot.
     *
     * @return array<int | string, string | int> | string
     */
    public static function palette(string $accent): array|string
    {
        return Color::all()[$accent] ?? $accent;
    }

    /**
     * Why the accent cannot be used, as a translation key, or null when it
     * can: an unknown name or a malformed hex, or a palette whose button and
     * link shades would not stand out from a light page (primary-600 on white)
     * or a dark one (primary-400 on gray-950), WCAG AA for interface parts,
     * 3:1. Filament evens out the lightness of a palette it generates, so a
     * hex rarely fails; the check holds whatever palette the colour becomes.
     */
    public static function problem(string $accent): ?string
    {
        if (! isset(Color::all()[$accent]) && ! preg_match('/^#[0-9a-f]{6}\z/i', $accent)) {
            return 'nitpick::themes.accent.invalid';
        }

        $palette = static::palette($accent);
        $palette = is_string($palette) ? Color::generatePalette($palette) : $palette;

        if (Color::calculateContrastRatio($palette[600], '#ffffff') < Color::WCAG_AA_NON_TEXT) {
            return 'nitpick::themes.accent.too_light';
        }

        if (Color::calculateContrastRatio($palette[400], Color::Zinc[950]) < Color::WCAG_AA_NON_TEXT) {
            return 'nitpick::themes.accent.too_dark';
        }

        return null;
    }

    /**
     * A CSS colour to show the accent with: the palette's 500 shade.
     */
    public static function swatch(string $accent): string
    {
        $palette = static::palette($accent);

        return is_string($palette) ? $palette : (string) $palette[500];
    }
}
