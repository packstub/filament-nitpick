<?php

namespace Packstub\Themes;

use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Vite;
use InvalidArgumentException;
use LogicException;
use Packstub\Themes\Contracts\Theme;
use Packstub\Themes\Events\ThemeChanged;
use Packstub\Themes\Filament\Pages\Appearance;
use Throwable;

class ThemesPlugin implements Plugin
{
    public const ID = 'packstub-themes';

    /** @var array<int, string | Theme> | Closure | null */
    protected array|Closure|null $themes = null;

    protected string|Theme|Closure|null $default = null;

    /** @var array<string, array<int | string, string | int> | string> */
    protected array $colors = [];

    protected string|Closure|null $customCss = null;

    protected bool $hasCustomCss = false;

    protected bool|Closure $userSelectable = false;

    protected bool $userMenuItem = true;

    /** @var class-string<Appearance> */
    protected string $appearancePage = Appearance::class;

    protected ?ThemeMode $forcedMode = null;

    protected bool $hasForcedMode = false;

    protected ?Panel $panel = null;

    /** @var array<int | string, Theme> */
    protected array $activeThemes = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(static::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return static::ID;
    }

    // ------------------------------------------------------------------
    // Configuration
    // ------------------------------------------------------------------

    /**
     * The themes this panel offers, by id, class name or instance. Defaults
     * to every registered theme.
     *
     * @param  array<int, string | Theme> | Closure  $themes
     */
    public function themes(array|Closure $themes): static
    {
        $this->themes = $themes;

        return $this;
    }

    /**
     * The theme users see until they pick one. A closure is evaluated per
     * request, so it can follow the tenant: fn () => Filament::getTenant()?->theme.
     */
    public function default(string|Theme|Closure|null $theme): static
    {
        $this->default = $theme;

        return $this;
    }

    /**
     * Colour slots applied on top of every theme's palette, in the shape of
     * Panel::colors(): ['primary' => Color::Indigo].
     *
     * @param  array<string, array<int | string, string | int> | string>  $colors
     */
    public function colors(array $colors): static
    {
        $this->colors = [...$this->colors, ...$colors];

        return $this;
    }

    /**
     * A stylesheet loaded after the active theme's, for per-project tweaks:
     * a path relative to public/ or an absolute URL.
     */
    public function customCss(string|Closure|null $path): static
    {
        $this->customCss = $path;
        $this->hasCustomCss = true;

        return $this;
    }

    /**
     * Let each user pick their own theme for this panel: adds the Appearance
     * page, a user-menu link to it, and live preview in the picker.
     */
    public function userSelectable(bool|Closure $condition = true, bool $userMenuItem = true): static
    {
        $this->userSelectable = $condition;
        $this->userMenuItem = $userMenuItem;

        return $this;
    }

    /**
     * @param  class-string<Appearance>  $page
     */
    public function appearancePage(string $page): static
    {
        $this->appearancePage = $page;

        return $this;
    }

    /**
     * Force a light/dark/system default regardless of the active theme's
     * preferred mode. Null hands the choice back to the theme.
     */
    public function forceMode(?ThemeMode $mode): static
    {
        $this->forcedMode = $mode;
        $this->hasForcedMode = true;

        return $this;
    }

    // ------------------------------------------------------------------
    // Resolution
    // ------------------------------------------------------------------

    /**
     * The themes offered on this panel, keyed by id.
     *
     * @return array<string, Theme>
     */
    public function getThemes(): array
    {
        $manager = $this->manager();
        $configured = $this->themes instanceof Closure ? ($this->themes)() : $this->themes;

        if ($configured === null) {
            $themes = $manager->all();
        } else {
            $themes = [];

            foreach ($configured as $theme) {
                $resolved = $manager->resolve($theme);
                $themes[$resolved->getId()] = $resolved;
            }
        }

        if ($themes === []) {
            throw new LogicException('No themes are registered. Register one with Themes::register() or keep the bundled ones.');
        }

        return $themes;
    }

    public function getDefaultTheme(): Theme
    {
        $themes = $this->getThemes();
        $default = $this->default instanceof Closure ? ($this->default)() : $this->default;
        $default ??= config('packstub-themes.default');

        if ($default !== null) {
            $resolved = $this->manager()->find($default);

            if ($resolved !== null && isset($themes[$resolved->getId()])) {
                return $themes[$resolved->getId()];
            }

            if ($this->default !== null) {
                $name = is_string($default) ? $default : $default::class;

                throw new InvalidArgumentException("Theme [{$name}] is not offered on the [{$this->getPanelId()}] panel.");
            }
        }

        return reset($themes);
    }

    /**
     * The theme rendered for this request: the signed-in user's choice when
     * user selection is on and the choice is still offered, else the default.
     */
    public function getActiveTheme(): Theme
    {
        $user = $this->currentUser();

        // Filament boots the panel before the session starts, so there is no
        // user yet on that first pass; the default applies and nothing is
        // memoised until a user is known.
        if ($user === null) {
            return $this->getDefaultTheme();
        }

        return $this->activeThemes[$user->getAuthIdentifier()] ??= $this->getUserTheme($user) ?? $this->getDefaultTheme();
    }

    public function getUserTheme(?Authenticatable $user = null): ?Theme
    {
        if (! $this->isUserSelectable()) {
            return null;
        }

        $user ??= $this->currentUser();

        if ($user === null) {
            return null;
        }

        $id = $this->manager()->store()->get($user, $this->getPanelId());

        return $id === null ? null : ($this->getThemes()[$id] ?? null);
    }

    /**
     * Save a user's theme for this panel. Null clears it so the default
     * applies again.
     */
    public function setUserTheme(string|Theme|null $theme, ?Authenticatable $user = null): void
    {
        $user ??= $this->currentUser();

        if ($user === null) {
            throw new LogicException('There is no signed-in user to save a theme for.');
        }

        $resolved = null;

        if ($theme !== null) {
            $resolved = $this->manager()->resolve($theme);

            if (! isset($this->getThemes()[$resolved->getId()])) {
                throw new InvalidArgumentException("Theme [{$resolved->getId()}] is not offered on the [{$this->getPanelId()}] panel.");
            }
        }

        $this->manager()->store()->set($user, $this->getPanelId(), $resolved?->getId());
        $this->activeThemes = [];

        event(new ThemeChanged($user, $this->getPanelId(), $resolved));
    }

    public function isUserSelectable(): bool
    {
        return (bool) value($this->userSelectable);
    }

    /**
     * A theme's palette with the config and plugin overrides applied.
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    public function getColorsFor(Theme $theme): array
    {
        return [
            ...$theme->getColors(),
            ...(array) config('packstub-themes.colors', []),
            ...$this->colors,
        ];
    }

    public function getCustomCss(): ?string
    {
        $path = $this->hasCustomCss ? value($this->customCss) : config('packstub-themes.custom_css');

        return filled($path) ? $path : null;
    }

    /**
     * The default light/dark mode while a theme is active: the forced mode,
     * else the config, else the theme's preference, else null (leave the
     * panel's own default).
     */
    public function getThemeMode(Theme $theme): ?ThemeMode
    {
        if ($this->hasForcedMode) {
            return $this->forcedMode;
        }

        $configured = config('packstub-themes.force_mode');

        if ($configured instanceof ThemeMode) {
            return $configured;
        }

        if (is_string($configured) && $configured !== '') {
            return ThemeMode::from($configured);
        }

        return $theme->getPreferredMode();
    }

    /**
     * @return class-string<Appearance>
     */
    public function getAppearancePage(): string
    {
        return $this->appearancePage;
    }

    public function getPanelId(): string
    {
        return $this->panel?->getId() ?? Filament::getCurrentOrDefaultPanel()?->getId() ?? 'default';
    }

    // ------------------------------------------------------------------
    // Panel integration
    // ------------------------------------------------------------------

    public function register(Panel $panel): void
    {
        $this->panel = $panel;

        // The panel boots (SetUpPanel) before the session starts, so this
        // first registration carries the default theme. Once Filament is
        // serving the request the user is known and the active theme's
        // palette and mode replace it below.
        $panel->colors(fn (): array => $this->getColorsFor($this->getActiveTheme()));

        Filament::serving(function () use ($panel): void {
            if (Filament::getCurrentPanel()?->getId() !== $panel->getId()) {
                return;
            }

            $this->applyActiveTheme($panel);
        });

        $panel->pages([$this->appearancePage]);

        if ($this->userMenuItem) {
            $panel->userMenuItems([
                'packstub-themes-appearance' => Action::make('packstub-themes-appearance')
                    ->label(fn (): string => __('packstub-themes::themes.appearance.title'))
                    ->icon(Heroicon::OutlinedSwatch)
                    ->url(fn (): string => $this->appearancePage::getUrl())
                    ->visible(fn (): bool => $this->isUserSelectable()),
            ]);
        }

        $panel->renderHook(PanelsRenderHook::HEAD_END, fn (): string => $this->renderHead());
    }

    public function boot(Panel $panel): void
    {
        $this->applyActiveTheme($panel);
    }

    /**
     * Point the panel's default mode and Filament's colour registry at the
     * active theme. Runs at panel boot (the default theme, or the user's when
     * the guard already knows them) and again once Filament is serving the
     * request with the session started.
     */
    protected function applyActiveTheme(Panel $panel): void
    {
        $theme = $this->getActiveTheme();
        $mode = $this->getThemeMode($theme);

        if ($mode !== null) {
            $panel->defaultThemeMode($mode);
        }

        FilamentColor::register($this->getColorsFor($theme));
    }

    // ------------------------------------------------------------------
    // Rendering
    // ------------------------------------------------------------------

    public function renderHead(): string
    {
        $theme = $this->getActiveTheme();
        $selectable = $this->isUserSelectable();

        $tags = [
            sprintf('<meta name="packstub-theme" content="%s">', e($theme->getId())),
        ];

        // Before the theme's stylesheets, so a stylesheet that sets a colour variable itself wins.
        if ($selectable) {
            $tags[] = $this->renderPaletteStyles();
        }

        foreach ($this->stylesheetHrefs($theme) as $index => $href) {
            $tags[] = sprintf(
                '<link rel="stylesheet"%s href="%s" data-packstub-theme="%s" data-packstub-theme-stylesheet data-navigate-track>',
                $index === 0 ? ' id="packstub-theme-active"' : '',
                e($href),
                e($theme->getId()),
            );
        }

        if ($custom = $this->getCustomCss()) {
            $href = filter_var($custom, FILTER_VALIDATE_URL) ? $custom : asset(ltrim($custom, '/'));

            $tags[] = sprintf('<link rel="stylesheet" id="packstub-theme-custom" href="%s" data-navigate-track>', e($href));
        }

        $tags[] = $this->renderBootstrapScript($theme, $selectable ? $this->buildManifest() : null);

        return implode("\n", array_filter($tags));
    }

    /**
     * Every offered palette as CSS variables scoped to the theme attribute, so
     * the picker can preview a theme without a round trip. Filament sets the
     * same variables on :root; this selector weighs the same and comes later,
     * so it wins, and a theme stylesheet that sets one (on :root or .dark)
     * comes later still and wins over it.
     */
    protected function renderPaletteStyles(): string
    {
        $blocks = [];

        foreach ($this->getThemes() as $id => $theme) {
            $variables = $this->manager()->cssVariables($this->getColorsFor($theme));

            if ($variables === []) {
                continue;
            }

            $declarations = implode(' ', array_map(
                fn (string $name, string $value): string => "--{$name}: {$value};",
                array_keys($variables),
                $variables,
            ));

            $blocks[] = sprintf('[data-packstub-theme="%s"] { %s }', e($id), $declarations);
        }

        if ($blocks === []) {
            return '';
        }

        return '<style id="packstub-themes-palettes">'.implode("\n", $blocks).'</style>';
    }

    /**
     * @return array{themes: array<string, array{hrefs: array<int, string>, label: string, mode: string | null}>}
     */
    protected function buildManifest(): array
    {
        $themes = [];

        foreach ($this->getThemes() as $id => $theme) {
            $themes[$id] = [
                'hrefs' => $this->stylesheetHrefs($theme),
                'label' => $theme->getLabel(),
                'mode' => $this->getThemeMode($theme)?->value,
            ];
        }

        return ['themes' => $themes];
    }

    /**
     * Stamps the active theme on <html> before the body paints and, when
     * users may switch, exposes window.PackstubThemes.preview(id). Nothing
     * here writes Filament's `theme` localStorage key on load: the user's
     * light/dark toggle is theirs. Only preview() adopts a theme's preferred
     * mode, because that is a deliberate choice.
     *
     * @param  array{themes: array<string, array{hrefs: array<int, string>, label: string, mode: string | null}>} | null  $manifest
     */
    protected function renderBootstrapScript(Theme $theme, ?array $manifest): string
    {
        $id = json_encode($theme->getId());
        $manifestJson = $manifest === null ? 'null' : json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return <<<HTML
            <script data-navigate-once>
            (function () {
                var html = document.documentElement;
                var manifest = {$manifestJson};

                function stamp() {
                    var meta = document.querySelector('meta[name="packstub-theme"]');
                    html.setAttribute('data-packstub-theme', meta ? meta.getAttribute('content') : {$id});
                }

                function applyMode(mode) {
                    if (! mode) { return; }
                    try { localStorage.setItem('theme', mode); } catch (e) {}
                    var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    html.classList.toggle('dark', dark);
                    if (window.Alpine && typeof Alpine.store === 'function' && Alpine.store('theme') !== undefined) {
                        Alpine.store('theme', dark ? 'dark' : 'light');
                    }
                }

                stamp();
                document.addEventListener('livewire:navigated', stamp);

                window.PackstubThemes = {
                    current: function () { return html.getAttribute('data-packstub-theme'); },
                    list: function () { return manifest ? Object.keys(manifest.themes) : []; },
                    preview: function (slug) {
                        if (! manifest || ! manifest.themes[slug]) { return false; }
                        var theme = manifest.themes[slug];
                        // The theme's stylesheets, in order: links already in the head are reused,
                        // new ones follow the last (ahead of the custom stylesheet), spare ones go.
                        var links = Array.prototype.slice.call(document.querySelectorAll('link[data-packstub-theme-stylesheet]'));
                        var custom = document.getElementById('packstub-theme-custom');
                        var last = null;
                        theme.hrefs.forEach(function (href, index) {
                            var link = links[index];
                            if (! link) {
                                link = document.createElement('link');
                                link.rel = 'stylesheet';
                                link.setAttribute('data-packstub-theme-stylesheet', '');
                                if (last) {
                                    last.insertAdjacentElement('afterend', link);
                                } else if (custom) {
                                    custom.insertAdjacentElement('beforebegin', link);
                                } else {
                                    document.head.appendChild(link);
                                }
                            }
                            if (index === 0) { link.id = 'packstub-theme-active'; }
                            link.setAttribute('href', href);
                            link.setAttribute('data-packstub-theme', slug);
                            last = link;
                        });
                        links.slice(theme.hrefs.length).forEach(function (link) { link.remove(); });
                        html.setAttribute('data-packstub-theme', slug);
                        var meta = document.querySelector('meta[name="packstub-theme"]');
                        if (meta) { meta.setAttribute('content', slug); }
                        applyMode(theme.mode);
                        document.dispatchEvent(new CustomEvent('packstub-theme-changed', { detail: { theme: slug } }));
                        return true;
                    }
                };
            })();
            </script>
            HTML;
    }

    /**
     * @return array<int, string>
     */
    protected function stylesheetHrefs(Theme $theme): array
    {
        $hrefs = [];

        foreach (array_values($theme->getStylesheets()) as $index => $stylesheet) {
            if ($this->manager()->isPublishedStylesheet($stylesheet)) {
                $hrefs[] = FilamentAsset::getStyleHref($this->manager()->assetId($theme, $index), package: ThemeManager::ASSET_PACKAGE);

                continue;
            }

            if (! str_starts_with($stylesheet, ThemeManager::VITE_PREFIX)) {
                $hrefs[] = $stylesheet;

                continue;
            }

            // An entry missing from the build (npm run build not run yet) is reported, not fatal:
            // the panel still renders, in the theme's colours.
            try {
                $hrefs[] = Vite::asset(substr($stylesheet, strlen(ThemeManager::VITE_PREFIX)));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $hrefs;
    }

    protected function currentUser(): ?Authenticatable
    {
        try {
            return Filament::auth()->user();
        } catch (Throwable) {
            return null;
        }
    }

    protected function manager(): ThemeManager
    {
        return app(ThemeManager::class);
    }
}
