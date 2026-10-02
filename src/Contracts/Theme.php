<?php

namespace Packstub\Themes\Contracts;

use Filament\Enums\ThemeMode;

/**
 * A theme: a Filament colour palette plus optional stylesheets that
 * restyle the panel. Implement this (or extend Packstub\Themes\Theme) and
 * register the class with the ThemeManager to make it available to panels.
 */
interface Theme
{
    /**
     * A slug used as the asset id, the `data-packstub-theme` value and the
     * stored preference: lowercase letters, digits and dashes.
     */
    public function getId(): string;

    public function getLabel(): string;

    public function getDescription(): ?string;

    /**
     * Filament colour slots, in the shape of Panel::colors():
     * ['primary' => Color::Sky, 'danger' => '#f43f5e', ...].
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    public function getColors(): array;

    /**
     * The light/dark mode the theme is designed for. It becomes the panel's
     * default mode while the theme is active; users can still toggle. Null
     * leaves the panel's own default alone.
     */
    public function getPreferredMode(): ?ThemeMode;

    /**
     * The theme's stylesheets, linked in this order while the theme is
     * active: absolute paths to CSS files (published as Filament assets),
     * URLs, or `vite:<entry>` for an entry of the app's Vite build. Empty
     * for a palette-only theme.
     *
     * @return array<int, string>
     */
    public function getStylesheets(): array;

    /**
     * Up to three CSS colours shown as swatches in the theme picker.
     *
     * @return array<int, string>
     */
    public function getSwatches(): array;
}
