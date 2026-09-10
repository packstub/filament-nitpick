<?php

namespace Packstub\Themes\Contracts;

use Filament\Enums\ThemeMode;

/**
 * A theme: a Filament colour palette plus an optional stylesheet that
 * restyles the panel. Implement this (or extend Packstub\Themes\Theme) and
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
     * Absolute path to a CSS file, or null for a palette-only theme.
     */
    public function getStylesheetPath(): ?string;

    /**
     * Up to three CSS colours shown as swatches in the theme picker.
     *
     * @return array<int, string>
     */
    public function getSwatches(): array;
}
