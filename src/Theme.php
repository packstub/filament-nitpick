<?php

namespace Packstub\Nitpick;

use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Contracts\Theme as ThemeContract;

/**
 * Sensible defaults for a theme: no description, no stylesheet, no preferred
 * mode, swatches derived from the palette. A palette-only theme is three lines:
 *
 *     class Ocean extends Theme
 *     {
 *         public function getId(): string { return 'ocean'; }
 *         public function getLabel(): string { return 'Ocean'; }
 *         public function getColors(): array { return ['primary' => Color::Teal]; }
 *     }
 *
 * Or without a class: Theme::make('ocean')->colors(['primary' => Color::Teal]).
 */
abstract class Theme implements ThemeContract
{
    /**
     * A theme defined inline: the id, then label(), colors(), stylesheet(), mode().
     */
    public static function make(string $id): FluentTheme
    {
        return new FluentTheme($id);
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getPreferredMode(): ?ThemeMode
    {
        return null;
    }

    /**
     * None: a palette-only theme. A theme that restyles the panel returns
     * its CSS files, URLs or `vite:` entries, in order.
     *
     * @return array<int, string>
     */
    public function getStylesheets(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function getSwatches(): array
    {
        return $this->swatchesFrom($this->getColors());
    }

    /**
     * The 500 shades of a palette's primary, info and success slots, the
     * ones it has, as CSS colours.
     *
     * @param  array<string, array<int | string, string | int> | string>  $colors
     * @return array<int, string>
     */
    protected function swatchesFrom(array $colors): array
    {
        $swatches = [];

        foreach (['primary', 'info', 'success'] as $slot) {
            if (! isset($colors[$slot])) {
                continue;
            }

            $palette = is_string($colors[$slot]) ? Color::generatePalette($colors[$slot]) : $colors[$slot];
            $shade = $palette[500] ?? $palette[600] ?? null;

            if (is_string($shade)) {
                $swatches[] = Color::convertToOklch($shade);
            }
        }

        return $swatches;
    }
}
