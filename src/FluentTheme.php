<?php

namespace Packstub\Themes;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Illuminate\Support\Str;
use Packstub\Themes\Support\PluginCapture;

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

    /** @var array<int, string> */
    protected array $stylesheets = [];

    protected Plugin|Closure|null $themePlugin = null;

    /** @var array{colors: array<string, array<int | string, string | int> | string>, stylesheets: array<int, string>} | null */
    protected ?array $captured = null;

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
        return $this->stylesheets($path === null ? [] : [$path]);
    }

    /**
     * Absolute paths to several CSS files, linked in this order.
     *
     * @param  array<int, string>  $paths
     */
    public function stylesheets(array $paths): static
    {
        $this->stylesheets = array_values($paths);

        return $this;
    }

    /**
     * A Filament theme plugin to offer as this theme: the palette and every
     * stylesheet it would give a panel become the theme's own, so the plugin
     * is not added to the panel. Configure it as its docs say:
     *
     *     ->themePlugin(fn () => NordThemePlugin::make()->corners('round'))
     *
     * colors() and stylesheets() set here apply on top of the plugin's.
     */
    public function themePlugin(Plugin|Closure|null $plugin): static
    {
        $this->themePlugin = $plugin;
        $this->captured = null;

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
        return [...$this->captured()['colors'], ...$this->colors];
    }

    public function getPreferredMode(): ?ThemeMode
    {
        return $this->mode;
    }

    public function getStylesheetPath(): ?string
    {
        return $this->getStylesheets()[0] ?? null;
    }

    public function getStylesheets(): array
    {
        return [...$this->captured()['stylesheets'], ...$this->stylesheets];
    }

    public function getSwatches(): array
    {
        return $this->swatches ?? parent::getSwatches();
    }

    /**
     * @return array{colors: array<string, array<int | string, string | int> | string>, stylesheets: array<int, string>}
     */
    protected function captured(): array
    {
        if ($this->themePlugin === null) {
            return ['colors' => [], 'stylesheets' => []];
        }

        return $this->captured ??= PluginCapture::of(
            $this->themePlugin instanceof Closure ? ($this->themePlugin)() : $this->themePlugin,
        );
    }
}
