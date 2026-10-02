<?php

namespace Packstub\Themes;

use Filament\Enums\ThemeMode;
use Illuminate\Support\Str;

/**
 * A theme defined inline, without a class of its own; Theme::make() returns one:
 *
 *     Themes::register(
 *         Theme::make('nord')
 *             ->colors(['primary' => Color::Sky])
 *             ->stylesheet(resource_path('css/themes/nord.css')),
 *     );
 */
class FluentTheme extends Theme
{
    protected ?string $label = null;

    protected ?string $description = null;

    /** @var array<string, array<int | string, string | int> | string> */
    protected array $colors = [];

    protected ?string $stylesheet = null;

    protected ?ThemeMode $mode = null;

    /** @var array<int, string> | null */
    protected ?array $swatches = null;

    final public function __construct(protected string $id) {}

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Filament colour slots, in the shape of Panel::colors().
     *
     * @param  array<string, array<int | string, string | int> | string>  $colors
     */
    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Absolute path to a CSS file, linked while the theme is active.
     */
    public function stylesheet(?string $path): static
    {
        $this->stylesheet = $path;

        return $this;
    }

    /**
     * The light/dark mode the theme is designed for.
     */
    public function mode(?ThemeMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Up to three CSS colours for the picker, instead of the ones derived from the palette.
     *
     * @param  array<int, string>  $swatches
     */
    public function swatches(array $swatches): static
    {
        $this->swatches = $swatches;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->id);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function getPreferredMode(): ?ThemeMode
    {
        return $this->mode;
    }

    public function getStylesheetPath(): ?string
    {
        return $this->stylesheet;
    }

    public function getSwatches(): array
    {
        return $this->swatches ?? parent::getSwatches();
    }
}
