<?php

namespace Packstub\Nitpick;

use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use InvalidArgumentException;
use Livewire\Livewire;
use LogicException;
use Packstub\Nitpick\Contracts\ConfiguresPanel;
use Packstub\Nitpick\Contracts\Theme;
use Packstub\Nitpick\Enums\Transition;
use Packstub\Nitpick\Events\ThemeChanged;
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Support\PanelServing;
use RuntimeException;
use Throwable;

class NitpickPlugin implements Plugin
{
    public const ID = 'nitpick';

    /**
     * The picker's columns in the Appearance modal: fixed, so a pick that
     * changes the panel's layout never moves the cards.
     *
     * @var array<string, int>
     */
    public const MODAL_COLUMNS = ['default' => 1, 'sm' => 2, 'lg' => 4];

    /** @var array<int, string | Theme> | Closure | null */
    protected array|Closure|null $themes = null;

    protected string|Theme|Closure|null $default = null;

    /** @var array<string, array<int | string, string | int> | string> */
    protected array $colors = [];

    protected string|Closure|null $customCss = null;

    protected bool $hasCustomCss = false;

    protected bool $hasPicker = false;

    protected bool|Closure $pickerCondition = true;

    protected bool $userMenuItem = true;

    protected bool|Closure $pickerModal = true;

    protected Transition|string|Closure|false|null $transition = null;

    /** @var class-string<Appearance> */
    protected string $pickerPage = Appearance::class;

    protected ?Closure $configurePicker = null;

    protected ?ThemeMode $forcedMode = null;

    protected bool $hasForcedMode = false;

    protected ?Panel $panel = null;

    /** @var array<int | string, Theme> */
    protected array $activeThemes = [];

    /**
     * The colours the active theme set on the panel while serving this
     * request, by theme id.
     *
     * @var array<string, array<string, array<int | string, string | int> | string>>
     */
    protected array $servingColors = [];

    /**
     * The user (auth identifier) and the theme of theirs that configured the
     * panel for this request, so a request that ends in a server error can be
     * put down to the theme.
     *
     * @var array{0: int | string, 1: string}|null
     */
    protected ?array $servedUserTheme = null;

    /**
     * How long, in seconds, a user's theme is set aside after a request
     * under it ended in a server error: the default theme is served to them
     * until then, or until they pick a theme again.
     */
    public const FAILED_THEME_TTL = 600;

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
     * page and a user-menu item that opens the picker in a modal. Every
     * argument is optional, and one left out keeps what an earlier call set:
     *
     *     ->picker()                                                     // on
     *     ->picker(fn (ThemePicker $picker) => $picker->descriptions())  // on, the cards configured
     *     ->picker(condition: fn (): bool => auth()->user()->isStaff())  // on for some users
     *     ->picker(modal: false)                                         // the menu item links to the page
     *     ->picker(userMenuItem: false)                                  // no menu item: the page only
     *     ->picker(page: MyAppearance::class)                            // your own page
     *     ->picker(transition: Transition::Sweep)                        // the swap animation, or false for none
     *
     * $using configures the picker on the page and in the modal, the way you
     * would your own ThemePicker field. The modal has a fixed width and fixed
     * columns, so it stays put while a theme changes the panel's layout
     * around it. The transition defaults to the config's (Transition::Fade).
     *
     * @param  class-string<Appearance> | null  $page
     */
    public function picker(
        ?Closure $using = null,
        bool|Closure|null $condition = null,
        bool|Closure|null $modal = null,
        ?bool $userMenuItem = null,
        ?string $page = null,
        Transition|string|Closure|false|null $transition = null,
    ): static {
        $this->hasPicker = true;
        $this->configurePicker = $using ?? $this->configurePicker;
        $this->pickerCondition = $condition ?? $this->pickerCondition;
        $this->pickerModal = $modal ?? $this->pickerModal;
        $this->userMenuItem = $userMenuItem ?? $this->userMenuItem;
        $this->pickerPage = $page ?? $this->pickerPage;
        $this->transition = $transition ?? $this->transition;

        return $this;
    }

    public function hasPicker(): bool
    {
        return $this->hasPicker && (bool) value($this->pickerCondition);
    }

    public function hasPickerModal(): bool
    {
        return (bool) value($this->pickerModal);
    }

    /**
     * @return class-string<Appearance>
     */
    public function getPickerPage(): string
    {
        return $this->pickerPage;
    }

    /**
     * The swap animation: the picker's, else the config's, else
     * Transition::Fade; null for none.
     */
    public function getTransition(): ?Transition
    {
        $transition = $this->transition !== null ? value($this->transition) : config('nitpick.transition', Transition::Fade->value);

        if ($transition === false || $transition === null || $transition === 'none') {
            return null;
        }

        return is_string($transition) ? Transition::from($transition) : $transition;
    }

    public function configurePicker(ThemePicker $picker): ThemePicker
    {
        if ($this->configurePicker !== null) {
            ($this->configurePicker)($picker);
        }

        return $picker;
    }

    /**
     * @deprecated Use picker(): userSelectable() is picker(condition: $condition, userMenuItem: $userMenuItem). Removed in 1.0.
     */
    public function userSelectable(bool|Closure $condition = true, bool $userMenuItem = true): static
    {
        return $this->picker(condition: $condition, userMenuItem: $userMenuItem);
    }

    /**
     * @deprecated Use picker(page: $page). Removed in 1.0.
     *
     * @param  class-string<Appearance>  $page
     */
    public function appearancePage(string $page): static
    {
        return $this->picker(page: $page);
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
        $default ??= config('nitpick.default');

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
        if (! $this->hasPicker()) {
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
        Cache::forget($this->failedThemeKey($user->getAuthIdentifier()));

        event(new ThemeChanged($user, $this->getPanelId(), $resolved));
    }

    /**
     * @deprecated Use hasPicker(). Removed in 1.0.
     */
    public function isUserSelectable(): bool
    {
        return $this->hasPicker();
    }

    /**
     * A theme's palette, with the colours it set on the panel while serving
     * this request, then the config and plugin overrides, applied.
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    public function getColorsFor(Theme $theme): array
    {
        return [
            ...$theme->getColors(),
            ...$this->servingColors[$theme->getId()] ?? [],
            ...(array) config('nitpick.colors', []),
            ...$this->colors,
        ];
    }

    public function getCustomCss(): ?string
    {
        $path = $this->hasCustomCss ? value($this->customCss) : config('nitpick.custom_css');

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

        $configured = config('nitpick.force_mode');

        if ($configured instanceof ThemeMode) {
            return $configured;
        }

        if (is_string($configured) && $configured !== '') {
            return ThemeMode::from($configured);
        }

        return $theme->getPreferredMode();
    }

    /**
     * @deprecated Use getPickerPage(). Removed in 1.0.
     *
     * @return class-string<Appearance>
     */
    public function getAppearancePage(): string
    {
        return $this->getPickerPage();
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

            $this->applyActiveTheme($panel, serving: true);
        });

        $panel->pages([$this->pickerPage]);

        if ($this->userMenuItem) {
            $panel->userMenuItems([
                'nitpick-appearance' => Action::make('nitpick-appearance')
                    ->label(fn (): string => __('nitpick::themes.appearance.title'))
                    ->icon(Heroicon::OutlinedSwatch)
                    ->url(fn (): ?string => $this->hasPickerModal() ? null : $this->pickerPage::getUrl())
                    ->modalHeading(fn (): string => __('nitpick::themes.appearance.title'))
                    ->modalDescription(fn (): string => __('nitpick::themes.appearance.subheading'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->schema(fn (): array => [
                        $this->configurePicker(ThemePicker::make('theme')->hiddenLabel()->columns(static::MODAL_COLUMNS)),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(fn (): string => __('nitpick::themes.appearance.close'))
                    ->visible(fn (): bool => $this->hasPicker()),
            ]);
        }

        $panel->renderHook(PanelsRenderHook::HEAD_END, fn (): string => $this->renderHead());

        Event::listen(RequestHandled::class, fn (RequestHandled $event) => $this->rememberFailedTheme($event));
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
    protected function applyActiveTheme(Panel $panel, bool $serving = false): void
    {
        $theme = $this->getActiveTheme();

        // A theme that configures the panel does it once the user is known:
        // while Filament serves the request, never on the earlier boot pass.
        if ($serving && $theme instanceof ConfiguresPanel && $this->manager()->configuresPanel($theme)) {
            if ($this->failedRecently($theme)) {
                $this->fallBack($theme);
                $this->applyActiveTheme($panel, serving: true);

                return;
            }

            try {
                $this->servingColors = [$theme->getId() => PanelServing::run($theme, $panel)];
            } catch (Throwable $exception) {
                $this->fallBack($theme, $exception);
                $this->applyActiveTheme($panel, serving: true);

                return;
            }

            $user = $this->currentUser();

            if ($user !== null && $theme->getId() !== $this->getDefaultTheme()->getId()) {
                $this->servedUserTheme = [$user->getAuthIdentifier(), $theme->getId()];
            }

            app()->terminating(function (): void {
                $this->servingColors = [];
                $this->servedUserTheme = null;
            });
        }

        $mode = $this->getThemeMode($theme);

        if ($mode !== null) {
            $panel->defaultThemeMode($mode);
        }

        FilamentColor::register($this->getColorsFor($theme));
    }

    /**
     * A user's theme that fails to configure the panel (its plugin throws in
     * register() or boot(), its panel() callback does) would turn every page
     * into an error, the picker's included, and lock the user into it. The
     * failure is reported and the panel's default theme is served for this
     * request instead, with a notice to the user; their choice is kept, so
     * the theme comes back once it works. The default theme has nothing to
     * fall back to, so its failure is the panel's own and is thrown. Without
     * an exception, the theme failed on an earlier request, while the page
     * rendered (failedRecently()), and that failure was reported then.
     */
    protected function fallBack(Theme $failed, ?Throwable $exception = null): void
    {
        $default = $this->getDefaultTheme();
        $user = $this->currentUser();

        if ($user === null || $default->getId() === $failed->getId()) {
            throw $exception ?? new LogicException("The default theme [{$failed->getId()}] has nothing to fall back to.");
        }

        $key = $user->getAuthIdentifier();
        $this->activeThemes[$key] = $default;

        app()->terminating(function () use ($key): void {
            unset($this->activeThemes[$key]);
        });

        if ($exception !== null) {
            report(new RuntimeException(
                "The theme [{$failed->getId()}] failed to configure the [{$this->getPanelId()}] panel, so the default theme [{$default->getId()}] was served instead: {$exception->getMessage()}",
                previous: $exception,
            ));
        }

        // Once per page: the Livewire requests a page makes (lazy widgets, polling) fall back too,
        // and a notice from each would stack up on the page.
        if (Livewire::isLivewireRequest()) {
            return;
        }

        Notification::make()
            ->warning()
            ->title(__('nitpick::themes.notifications.unavailable.title', ['theme' => $failed->getLabel()]))
            ->body(__('nitpick::themes.notifications.unavailable.body', ['default' => $default->getLabel()]))
            ->send();
    }

    /**
     * A theme can configure the panel without a fault and still fail later,
     * while the page renders: its plugin turns on a feature the app is not
     * ready for (Rail's database notifications without a `notifications`
     * table). Every page is then an error for the user who picked it, the
     * picker's included. So a request that ends in a server error while a
     * user's own theme configured the panel sets that theme aside for them
     * for FAILED_THEME_TTL seconds: the default is served meanwhile, with the
     * notice (fallBack()). The error itself was reported by the app's handler.
     * An unrelated error sets the theme aside too, which costs the user their
     * theme for a while, never the panel; picking a theme again clears it.
     */
    protected function rememberFailedTheme(RequestHandled $event): void
    {
        if ($this->servedUserTheme === null || $event->response->getStatusCode() < 500) {
            return;
        }

        [$user, $theme] = $this->servedUserTheme;
        $this->servedUserTheme = null;

        Cache::put($this->failedThemeKey($user), $theme, static::FAILED_THEME_TTL);

        Log::warning("A request on the [{$this->getPanelId()}] panel ended in a server error while the theme [{$theme}] configured it, so the panel's default theme is served to this user for the next ".intdiv(static::FAILED_THEME_TTL, 60).' minutes, or until they pick a theme. If the error comes from the theme, complete its setup or stop offering it.', [
            'theme' => $theme,
            'panel' => $this->getPanelId(),
            'user' => $user,
            'status' => $event->response->getStatusCode(),
        ]);
    }

    protected function failedRecently(Theme $theme): bool
    {
        $user = $this->currentUser();

        return $user !== null
            && $theme->getId() !== $this->getDefaultTheme()->getId()
            && Cache::get($this->failedThemeKey($user->getAuthIdentifier())) === $theme->getId();
    }

    protected function failedThemeKey(int|string $user): string
    {
        return "nitpick:failed-theme:{$this->getPanelId()}:{$user}";
    }

    // ------------------------------------------------------------------
    // Rendering
    // ------------------------------------------------------------------

    public function renderHead(): string
    {
        $theme = $this->getActiveTheme();
        $selectable = $this->hasPicker();

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

        // The cover a transition paints over the panel, set before the body paints so a page that
        // a pick reloaded can start under it.
        if ($selectable) {
            $tags[] = '<style id="nitpick-cover">html[data-nitpick-cover]::after { content: ""; position: fixed; inset: 0; z-index: 2147483647; pointer-events: none; background: var(--nitpick-cover-color, #000); } #nitpick-cover-layer { position: fixed; inset: 0; z-index: 2147483647; pointer-events: none; overflow: hidden; } #nitpick-cover-layer > div { position: absolute; inset: 0; will-change: transform, opacity; } html[data-nitpick-still] *, html[data-nitpick-still] *::before, html[data-nitpick-still] *::after { transition: none !important; }</style>';
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

        return '<style id="nitpick-palettes">'.implode("\n", $blocks).'</style>';
    }

    /**
     * @return array{themes: array<string, array{hrefs: array<int, string>, label: string, mode: string | null, reload: bool}>, transition: string | null}
     */
    protected function buildManifest(): array
    {
        $themes = [];

        foreach ($this->getThemes() as $id => $theme) {
            $themes[$id] = [
                'hrefs' => $this->stylesheetHrefs($theme),
                'label' => $theme->getLabel(),
                'mode' => $this->getThemeMode($theme)?->value,
                'reload' => $this->manager()->configuresPanel($theme),
            ];
        }

        return ['themes' => $themes, 'transition' => $this->getTransition()?->value];
    }

    /**
     * Stamps the active theme on <html> before the body paints and, when
     * users may switch, exposes window.PackstubThemes: pick(id) changes the
     * theme with the panel's transition, preview(id) without one. Nothing
     * here writes Filament's light/dark localStorage key on load: the user's
     * light/dark toggle is theirs. Only a pick adopts a theme's preferred
     * mode, because that is a deliberate choice.
     *
     * A page that a pick reloaded starts under the transition's cover (a
     * note in sessionStorage, read here before the body paints), which lifts
     * once the page has loaded, with the picker's modal open again when the
     * pick came from it.
     *
     * @param  array{themes: array<string, array{hrefs: array<int, string>, label: string, mode: string | null, reload: bool}>, transition: string | null} | null  $manifest
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
                var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var note = 'nitpick:cover';

                function stamp() {
                    var meta = document.querySelector('meta[name="packstub-theme"]');
                    html.setAttribute('data-packstub-theme', meta ? meta.getAttribute('content') : {$id});
                }

                function isDark(mode) {
                    if (! mode) { return html.classList.contains('dark'); }
                    return mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                }

                function applyMode(mode) {
                    if (! mode) { return; }
                    // Filament 5.10.2 renamed its key from `theme` to `filament:theme:mode`: both are written.
                    try { localStorage.setItem('filament:theme:mode', mode); localStorage.setItem('theme', mode); } catch (e) {}
                    var dark = isDark(mode);
                    html.classList.toggle('dark', dark);
                    if (window.Alpine && typeof Alpine.store === 'function' && Alpine.store('theme') !== undefined) {
                        Alpine.store('theme', dark ? 'dark' : 'light');
                    }
                    // The light/dark switcher in the user menu read the mode once, when the page loaded:
                    // set its own state, as a click on it would, so it marks the mode now in use.
                    if (window.Alpine && typeof Alpine['\$data'] === 'function') {
                        document.querySelectorAll('.fi-theme-switcher').forEach(function (switcher) {
                            var data = Alpine['\$data'](switcher);
                            if (data && 'theme' in data) { data.theme = mode; }
                        });
                    }
                }

                // Whether changing to a theme takes a page load: it, or the active one, configures the panel.
                function needsReload(slug) {
                    var active = manifest.themes[html.getAttribute('data-packstub-theme')];
                    return !! (manifest.themes[slug].reload || (active && active.reload));
                }

                // Points the head at a theme. Returns the stylesheet links it changed, or null for a
                // theme that configures the panel: it shows only after a page load (the picker reloads
                // once the pick is saved), so only its light/dark mode is taken now.
                function apply(slug) {
                    var theme = manifest.themes[slug];
                    if (needsReload(slug)) {
                        applyMode(theme.mode);
                        return null;
                    }
                    // The theme's stylesheets, in order: links already in the head are reused,
                    // new ones follow the last (ahead of the custom stylesheet), spare ones go.
                    var links = Array.prototype.slice.call(document.querySelectorAll('link[data-packstub-theme-stylesheet]'));
                    var custom = document.getElementById('packstub-theme-custom');
                    var last = null;
                    var changed = [];
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
                        if (link.getAttribute('href') !== href) {
                            link.setAttribute('href', href);
                            changed.push(link);
                        }
                        link.setAttribute('data-packstub-theme', slug);
                        last = link;
                    });
                    links.slice(theme.hrefs.length).forEach(function (link) { link.remove(); });
                    html.setAttribute('data-packstub-theme', slug);
                    var meta = document.querySelector('meta[name="packstub-theme"]');
                    if (meta) { meta.setAttribute('content', slug); }
                    applyMode(theme.mode);
                    document.dispatchEvent(new CustomEvent('packstub-theme-changed', { detail: { theme: slug } }));
                    return changed;
                }

                // The transition's cover: a fixed layer over the page whose shape animates only its
                // transform or opacity, which the browser runs off the main thread, so the motion stays
                // smooth while the theme's stylesheets load and the page restyles under it. A page that
                // a pick reloaded starts under html::after (styled in #nitpick-cover) until the layer
                // takes over.
                var transitions = {
                    fade: { in: 220, out: 320, keyframes: function (coming) { return coming ? [{ opacity: 0 }, { opacity: 1 }] : [{ opacity: 1 }, { opacity: 0 }]; } },
                    slide: { in: 320, out: 420, keyframes: function (coming) { return coming ? [{ transform: 'translateX(-100%)' }, { transform: 'translateX(0)' }] : [{ transform: 'translateX(0)' }, { transform: 'translateX(100%)' }]; } },
                    sweep: { in: 400, out: 520, shape: sweep, keyframes: function () { return sweepKeyframes(); } }
                };

                // The README sweep: a half-plane whose edge turns about a point below the panel's right
                // corner. Coming, it covers what lies below that edge, so the panel fills
                // from the left as the edge rises; going, what lies above it, so it empties the same way.
                // The half-plane is a rectangle reaching just past the panel's farthest corner from the
                // point, so the layer the browser moves stays as small as it can be.
                function sweepPivot() {
                    return [window.innerWidth * 1.02, window.innerHeight * 1.55];
                }

                // The share of the panel the edge has passed at this angle: the panel's rectangle cut by
                // the edge, measured.
                function sweptShare(angle) {
                    var w = window.innerWidth, h = window.innerHeight, p = sweepPivot(), r = angle * Math.PI / 180;
                    var dx = -Math.cos(r), dy = -Math.sin(r), corners = [[0, 0], [w, 0], [w, h], [0, h]], cut = [], area = 0;
                    var side = function (q) { return dx * (q[1] - p[1]) - dy * (q[0] - p[0]); };
                    corners.forEach(function (a, i) {
                        var b = corners[(i + 1) % 4], sa = side(a), sb = side(b);
                        if (sa < 0) { cut.push(a); }
                        if ((sa < 0) !== (sb < 0)) { var t = sa / (sa - sb); cut.push([a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t]); }
                    });
                    cut.forEach(function (a, i) { var b = cut[(i + 1) % cut.length]; area += a[0] * b[1] - b[0] * a[1]; });
                    return Math.abs(area) / 2 / (w * h);
                }

                // The edge turns only while it crosses the panel, from the bottom-left corner to the
                // top-right one. Near the point it sweeps little of the panel per degree, far from it a
                // lot, so the keyframes are set by area: the panel fills (or empties) at the pace the
                // easing gives, never in a leap.
                function sweepKeyframes() {
                    var w = window.innerWidth, h = window.innerHeight, p = sweepPivot(), degrees = 180 / Math.PI, steps = 16, frames = [];
                    var from = Math.atan2(p[1] - h, p[0]) * degrees - 1, to = Math.atan2(p[1], p[0] - w) * degrees + 1;
                    for (var i = 0; i <= steps; i++) {
                        var lo = from, hi = to;
                        for (var j = 0; j < 24; j++) { var mid = (lo + hi) / 2; if (sweptShare(mid) < i / steps) { lo = mid; } else { hi = mid; } }
                        frames.push({ transform: 'rotate(' + (i === 0 ? from : i === steps ? to : (lo + hi) / 2) + 'deg)', offset: i / steps });
                    }
                    return frames;
                }

                function sweep(shape, coming) {
                    var p = sweepPivot(), x = p[0], y = p[1], reach = Math.ceil(Math.hypot(x, y)) + 2;
                    shape.style.inset = 'auto';
                    shape.style.width = (reach * 2) + 'px';
                    shape.style.height = reach + 'px';
                    shape.style.left = (x - reach) + 'px';
                    shape.style.top = (coming ? y : y - reach) + 'px';
                    shape.style.transformOrigin = coming ? '50% 0' : '50% 100%';
                }

                function transitionName(name) {
                    return name && transitions[name] && ! reducedMotion ? name : null;
                }

                function colorFor(mode) {
                    return isDark(mode) ? '#000' : '#fff';
                }

                // Web fonts the new theme brings, or 400 ms, whichever comes first, so text does not swap
                // its font while the cover lifts.
                function fontsReady() {
                    if (! document.fonts || ! document.fonts.ready) { return Promise.resolve(); }
                    return Promise.race([document.fonts.ready, new Promise(function (resolve) { setTimeout(resolve, 400); })]);
                }

                function nextFrame() {
                    return new Promise(function (resolve) { requestAnimationFrame(function () { requestAnimationFrame(resolve); }); });
                }

                // Resolves once the page has stopped changing under the cover: no DOM change for
                // quietFor ms (a Livewire response, a lazy widget, the reopened modal), then two frames
                // in a row on time, so the reveal starts on a browser with nothing else to do. Gives up
                // waiting after cap ms.
                function settled(quietFor, cap) {
                    return new Promise(function (resolve) {
                        var quiet = null, limit = null, done = false;
                        var observer = new MutationObserver(function (records) {
                            var ours = records.every(function (record) {
                                return (layer && layer.contains(record.target)) || (record.target === html && /^data-nitpick-/.test(record.attributeName || ''));
                            });
                            if (! ours) { clearTimeout(quiet); quiet = setTimeout(steady, quietFor); }
                        });
                        function finish() {
                            if (done) { return; }
                            done = true;
                            observer.disconnect();
                            clearTimeout(quiet);
                            clearTimeout(limit);
                            resolve();
                        }
                        function steady() {
                            observer.disconnect();
                            var last = null, onTime = 0;
                            requestAnimationFrame(function frame(now) {
                                if (done) { return; }
                                if (last !== null) { onTime = now - last < 24 ? onTime + 1 : 0; }
                                last = now;
                                if (onTime >= 2) { return finish(); }
                                requestAnimationFrame(frame);
                            });
                        }
                        observer.observe(html, { subtree: true, childList: true, attributes: true, characterData: true });
                        quiet = setTimeout(steady, quietFor);
                        limit = setTimeout(finish, cap);
                    });
                }

                var layer = null;
                var coverColor = null;

                // Holds the panel's own CSS transitions while a swap runs: without it, every element
                // eases from the old theme's colours to the new ones as the cover lifts, which reads as
                // the reveal fading.
                function still(on) {
                    if (on) { html.setAttribute('data-nitpick-still', ''); } else { html.removeAttribute('data-nitpick-still'); }
                }

                // Runs the cover in (coming) or out. Resolves when it is done.
                function animate(name, coming) {
                    var transition = transitions[name];
                    if (! layer) {
                        layer = document.createElement('div');
                        layer.id = 'nitpick-cover-layer';
                        layer.setAttribute('aria-hidden', 'true');
                        layer.appendChild(document.createElement('div'));
                    }
                    var shape = layer.firstChild;
                    shape.removeAttribute('style');
                    shape.style.background = coverColor;
                    if (transition.shape) { transition.shape(shape, coming); }
                    (document.body || html).appendChild(layer);
                    // The static cover of a reloaded page hands over to the layer in the same frame.
                    html.removeAttribute('data-nitpick-cover');
                    if (typeof shape.animate !== 'function') {
                        if (! coming) { layer.remove(); }
                        return Promise.resolve();
                    }
                    var animation = shape.animate(transition.keyframes(coming), {
                        duration: coming ? transition.in : transition.out,
                        // A sine in-out both ways: it starts and settles gently, and its fastest frame
                        // moves the cover not much more than an even pace would.
                        easing: 'cubic-bezier(0.37, 0, 0.63, 1)',
                        fill: 'both'
                    });
                    return animation.finished.then(function () { if (! coming) { layer.remove(); } });
                }

                // The stylesheets a pick swapped, loaded or given up on after 1.5 s.
                function loaded(links) {
                    var loads = links.map(function (link) {
                        return new Promise(function (resolve) {
                            link.addEventListener('load', resolve, { once: true });
                            link.addEventListener('error', resolve, { once: true });
                        });
                    });
                    return Promise.race([Promise.all(loads), new Promise(function (resolve) { setTimeout(resolve, 1500); })]);
                }

                function pickerInModal() {
                    return !! document.querySelector('.fi-modal .pst-theme-picker');
                }

                // Opens the Appearance modal from its user-menu item, and resolves once the picker
                // shows (or after 2 s).
                function reopenPicker() {
                    var trigger = Array.prototype.filter.call(document.querySelectorAll('button, a'), function (element) {
                        return (element.getAttribute('wire:click') || '').indexOf('nitpick-appearance') !== -1;
                    })[0];
                    if (! trigger) { return Promise.resolve(); }
                    trigger.click();
                    return new Promise(function (resolve) {
                        var waited = 0;
                        (function check() {
                            if (pickerInModal() || waited >= 2000) { return resolve(); }
                            waited += 50;
                            setTimeout(check, 50);
                        })();
                    });
                }

                stamp();
                document.addEventListener('livewire:navigated', stamp);

                // A page that a pick reloaded: start under the cover, and lift it once the page is
                // whole: Livewire started, the modal open again when the pick came from it (without its
                // fade), lazy widgets loaded and nothing else changing. Never longer than 2.5 s.
                var reloaded = null;
                try { reloaded = JSON.parse(sessionStorage.getItem(note) || 'null'); sessionStorage.removeItem(note); } catch (e) {}
                if (reloaded && Date.now() - reloaded.at < 10000) {
                    var reveal = transitionName(reloaded.transition);
                    var lifted = false;
                    var started = new Promise(function (resolve) {
                        if (window.Livewire && window.Livewire.all && window.Livewire.all().length) { return resolve(); }
                        document.addEventListener('livewire:initialized', resolve, { once: true });
                    });
                    var reopened = null;
                    var lift = function () {
                        if (lifted) { return; }
                        var whole = Promise.all([started, reopened, fontsReady()]);
                        Promise.race([whole, new Promise(function (resolve) { setTimeout(resolve, 1800); })])
                            .then(function () { return settled(120, 700); })
                            .then(function () {
                                if (lifted) { return; }
                                lifted = true;
                                return reveal ? animate(reveal, false) : null;
                            })
                            .then(function () { still(false); });
                    };
                    if (reveal) {
                        coverColor = reloaded.color;
                        html.style.setProperty('--nitpick-cover-color', reloaded.color);
                        html.setAttribute('data-nitpick-cover', '');
                        still(true);
                        // Never leave the panel covered: lift it after 4 s whatever happens.
                        setTimeout(function () { if (! lifted) { lifted = true; html.removeAttribute('data-nitpick-cover'); } still(false); }, 4000);
                    }
                    // The user-menu item works once Livewire has started.
                    if (reloaded.reopen) { reopened = started.then(reopenPicker); }
                    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', lift, { once: true }); } else { setTimeout(lift, 0); }
                }

                var covered = null;
                var uncover = null;
                var swap = null;

                window.PackstubThemes = {
                    current: function () { return html.getAttribute('data-packstub-theme'); },
                    list: function () { return manifest ? Object.keys(manifest.themes) : []; },
                    transition: function () { return transitionName(manifest && manifest.transition); },
                    // Changes the theme in the head without a transition. False for a theme that shows
                    // only after a page load.
                    preview: function (slug) {
                        if (! manifest || ! manifest.themes[slug]) { return false; }
                        return apply(slug) !== null;
                    },
                    // Changes the theme with the panel's transition: cover, change, lift. A theme that shows
                    // only after a page load is covered only when a reload() follows ({ reload: true }, as the
                    // picker passes when it saves the pick); otherwise only its light/dark mode is taken, as
                    // in preview(). A pick while the cover is up changes the running swap's theme instead of
                    // starting another cover over it.
                    pick: function (slug, options) {
                        var name = this.transition();
                        if (! name || ! manifest || ! manifest.themes[slug]) { return this.preview(slug); }
                        if (swap) { swap.slug = slug; return true; }
                        if (needsReload(slug) && ! (options && options.reload)) { return this.preview(slug); }
                        swap = { slug: slug };
                        coverColor = colorFor(manifest.themes[slug].mode);
                        covered = animate(name, true);
                        covered.then(function change() {
                            still(true);
                            var target = swap.slug;
                            var changed = apply(target);
                            var lift = function () {
                                // Picked again under the cover: change to that theme before lifting.
                                if (swap.slug !== target) { return change(); }
                                swap = null;
                                covered = null;
                                return animate(name, false).then(function () { still(false); });
                            };
                            if (changed === null) {
                                // The picker calls reload() once the pick is saved; lift the cover if it never does.
                                uncover = setTimeout(lift, 5000);
                                return;
                            }
                            // Lift once the new theme has painted and the pick has saved, so the reveal never
                            // starts mid-restyle.
                            loaded(changed).then(fontsReady).then(function () { return settled(80, 900); }).then(lift);
                        });
                        return true;
                    },
                    // Reloads the page once the cover is in, so the next page starts under it.
                    reload: function () {
                        var name = this.transition();
                        var reopen = pickerInModal();
                        (covered || Promise.resolve()).then(function () {
                            clearTimeout(uncover);
                            try {
                                sessionStorage.setItem(note, JSON.stringify({
                                    transition: name,
                                    color: coverColor || colorFor(null),
                                    reopen: reopen,
                                    at: Date.now()
                                }));
                            } catch (e) {}
                            window.location.reload();
                        });
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
