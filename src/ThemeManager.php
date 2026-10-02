<?php

namespace Packstub\Themes;

use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use InvalidArgumentException;
use Packstub\Themes\Contracts\Theme;
use Packstub\Themes\Contracts\ThemePreferenceStore;

/**
 * The theme registry. Themes register here (the bundled ones from this
 * package's service provider, others from theirs) and panels pick from it.
 */
class ThemeManager
{
    public const ASSET_PACKAGE = 'packstub/filament-themes';

    /** @var array<string, Theme> */
    protected array $themes = [];

    /**
     * A stylesheet written `vite:<entry>` is an entry of the app's Vite
     * build (vite.config.js `input`), linked from the build's manifest.
     */
    public const VITE_PREFIX = 'vite:';

    public function register(string|Theme ...$themes): static
    {
        foreach ($themes as $theme) {
            $instance = is_string($theme) ? app($theme) : $theme;

            if (! $instance instanceof Theme) {
                $class = is_object($instance) ? $instance::class : (string) $theme;

                throw new InvalidArgumentException("[{$class}] does not implement ".Theme::class.'.');
            }

            $id = $instance->getId();

            if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
                throw new InvalidArgumentException("Theme id [{$id}] must contain only lowercase letters, digits and dashes.");
            }

            $this->themes[$id] = $instance;
            $this->registerStylesheet($instance);
        }

        return $this;
    }

    /**
     * @return array<string, Theme>
     */
    public function all(): array
    {
        return $this->themes;
    }

    /**
     * Find a registered theme by id, class name or instance.
     */
    public function find(string|Theme $theme): ?Theme
    {
        if ($theme instanceof Theme) {
            return $this->themes[$theme->getId()] ?? $theme;
        }

        if (isset($this->themes[$theme])) {
            return $this->themes[$theme];
        }

        if (class_exists($theme)) {
            foreach ($this->themes as $registered) {
                if ($registered instanceof $theme) {
                    return $registered;
                }
            }
        }

        return null;
    }

    public function resolve(string|Theme $theme): Theme
    {
        $resolved = $this->find($theme);

        if ($resolved === null) {
            $name = is_string($theme) ? $theme : $theme::class;

            throw new InvalidArgumentException("Theme [{$name}] is not registered. Register it with Themes::register() or check the id.");
        }

        return $resolved;
    }

    public function has(string|Theme $theme): bool
    {
        return $this->find($theme) !== null;
    }

    /**
     * A stylesheet is a CSS file, published as a Filament asset, unless it
     * is a URL or a `vite:` entry: those are linked where they already are.
     */
    public function isPublishedStylesheet(string $stylesheet): bool
    {
        return ! str_starts_with($stylesheet, static::VITE_PREFIX)
            && ! str_starts_with($stylesheet, 'http://')
            && ! str_starts_with($stylesheet, 'https://')
            && ! str_starts_with($stylesheet, '//');
    }

    /**
     * The asset id of a theme's stylesheet: `packstub-theme-<id>` for the
     * first, `packstub-theme-<id>--2` and up for the ones after it (an id
     * never holds two dashes in a row, so the two never collide).
     */
    public function assetId(Theme $theme, int $index = 0): string
    {
        return 'packstub-theme-'.$theme->getId().($index === 0 ? '' : '--'.($index + 1));
    }

    public function store(): ThemePreferenceStore
    {
        return app(ThemePreferenceStore::class);
    }

    /**
     * The resolved CSS variables Filament renders for a palette
     * (`primary-500` => `oklch(...)`), so a theme's colours can be applied
     * from a stylesheet as well as from the panel's colour registration.
     *
     * @param  array<string, array<int | string, string | int> | string>  $colors
     * @return array<string, string>
     */
    public function cssVariables(array $colors): array
    {
        $variables = [];

        foreach ($colors as $name => $color) {
            $palette = is_string($color)
                ? Color::generatePalette($color)
                : array_map(
                    fn (string|int $shade): string|int => is_string($shade) ? Color::convertToOklch($shade) : $shade,
                    $color,
                );

            foreach (array_keys($palette) as $shade) {
                $variables["{$name}-{$shade}"] = $this->resolveShade($palette, $shade);
            }
        }

        return $variables;
    }

    public static function userModel(): string
    {
        return config('packstub-themes.user_model')
            ?? config('auth.providers.users.model');
    }

    /**
     * The connection the preferences table lives on: the configured one, or
     * the user model's own connection (null = the application default).
     */
    public static function connectionName(): ?string
    {
        $connection = config('packstub-themes.connection');

        if (filled($connection)) {
            return $connection;
        }

        $userModel = static::userModel();

        return $userModel ? (new $userModel)->getConnectionName() : null;
    }

    public static function tableName(): string
    {
        return config('packstub-themes.table', 'theme_preferences');
    }

    protected function registerStylesheet(Theme $theme): void
    {
        $assets = [];

        foreach (array_values($theme->getStylesheets()) as $index => $path) {
            if ($this->isPublishedStylesheet($path)) {
                $assets[] = Css::make($this->assetId($theme, $index), $path)->loadedOnRequest();
            }
        }

        if ($assets !== []) {
            FilamentAsset::register($assets, package: static::ASSET_PACKAGE);
        }
    }

    /**
     * Mirrors Filament's own shade resolution: a shade may alias another
     * shade by key, and `0` means white.
     *
     * @param  array<int | string, string | int>  $palette
     */
    protected function resolveShade(array $palette, string|int $shade): string
    {
        $color = $palette[$shade];

        while (! is_string($color) || ! str_starts_with($color, 'oklch(')) {
            if ($color === 0) {
                return 'oklch(1 0 0)';
            }

            $color = $palette[$color];
        }

        return $color;
    }
}
