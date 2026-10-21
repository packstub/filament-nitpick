<?php

namespace Packstub\Nitpick;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Panel;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Packstub\Nitpick\Contracts\ConfiguresPanel;
use Packstub\Nitpick\Support\PluginCapture;
use Throwable;

/**
 * A theme defined inline, without a class of its own; Theme::make() returns one:
 *
 *     Themes::register(
 *         Theme::make('nord')
 *             ->colors(['primary' => Color::Sky])
 *             ->stylesheet(resource_path('css/themes/nord.css')),
 *     );
 */
class FluentTheme extends Theme implements ConfiguresPanel
{
    protected ?string $label = null;

    protected ?string $description = null;

    /** @var array<string, array<int | string, string | int> | string> */
    protected array $colors = [];

    /** @var array<int, string> */
    protected array $stylesheets = [];

    protected Plugin|Closure|null $plugin = null;

    protected ?Closure $serving = null;

    /** @var array<string, array<int | string, string | int> | string> | null */
    protected ?array $servingColors = null;

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
     * A stylesheet linked while the theme is active: the absolute path to a
     * CSS file, a URL, or `vite:<entry>` for an entry of the app's Vite build.
     */
    public function stylesheet(?string $path): static
    {
        return $this->stylesheets($path === null ? [] : [$path]);
    }

    /**
     * Several stylesheets (paths, URLs or `vite:` entries), linked in this order.
     *
     * @param  array<int, string>  $paths
     */
    public function stylesheets(array $paths): static
    {
        $this->stylesheets = array_values($paths);

        return $this;
    }

    /**
     * A Filament theme plugin to offer as this theme: it joins the panel
     * while this is the active theme, and only then, so it styles the panel
     * the way its own docs describe. Configure it as they say:
     *
     *     ->plugin(fn () => NordThemePlugin::make()->corners('round'))
     *
     * A closure makes a fresh plugin for each request. colors() and
     * stylesheets() set here apply on top of the plugin's; its colours give
     * the theme its swatches in the picker. Picking it reloads the page.
     */
    public function plugin(Plugin|Closure|null $plugin): static
    {
        $this->plugin = $plugin;
        $this->servingColors = null;

        return $this;
    }

    /**
     * Configure the panel while this is the active theme, the way a panel
     * provider would. A styling plugin can be added here too:
     *
     *     ->serving(fn (Panel $panel) => $panel->font('Fraunces')->plugin(PressFilamentTheme::make()))
     *
     * See ConfiguresPanel for what runs when; picking such a theme reloads the page.
     * The callback also runs once on a scratch panel that is never served,
     * so the colours it sets give the theme its swatches in the picker.
     */
    public function serving(?Closure $callback): static
    {
        $this->serving = $callback;
        $this->servingColors = null;

        return $this;
    }

    public function servingPanel(Panel $panel): void
    {
        if ($this->plugin !== null) {
            $panel->plugin($this->resolvePlugin());
        }

        if ($this->serving !== null) {
            ($this->serving)($panel, $this);
        }
    }

    public function configuresPanel(): bool
    {
        return $this->plugin !== null || $this->serving !== null;
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
        return $this->getStylesheets()[0] ?? null;
    }

    public function getStylesheets(): array
    {
        return $this->stylesheets;
    }

    public function getSwatches(): array
    {
        return $this->swatches ?? $this->swatchesFrom([...$this->servingColors(), ...$this->colors]);
    }

    /**
     * The colours the theme's plugin and serving() callback set, read from a
     * scratch panel: only the picker's swatches use them, the real panel gets
     * them from the plugin itself.
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    protected function servingColors(): array
    {
        if (! $this->configuresPanel()) {
            return [];
        }

        // Read without a request or a user: a callback that needs one is reported, and the theme
        // keeps the colours given to it directly.
        try {
            return $this->servingColors ??= PluginCapture::colorsFrom(fn (Panel $panel) => $this->servingPanel($panel));
        } catch (Throwable $exception) {
            report($exception);

            return $this->servingColors = [];
        }
    }

    protected function resolvePlugin(): Plugin
    {
        $plugin = $this->plugin instanceof Closure ? ($this->plugin)() : $this->plugin;

        if (! $plugin instanceof Plugin) {
            $given = is_object($plugin) ? $plugin::class : gettype($plugin);

            throw new InvalidArgumentException("Theme::make()->plugin() expects a Filament plugin, [{$given}] given.");
        }

        return $plugin;
    }
}
