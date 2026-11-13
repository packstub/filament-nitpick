<?php

namespace Packstub\Nitpick;

use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use InvalidArgumentException;
use Livewire\Livewire;
use LogicException;
use Packstub\Nitpick\Contracts\ConfiguresPanel;
use Packstub\Nitpick\Contracts\StoresAccents;
use Packstub\Nitpick\Contracts\Theme;
use Packstub\Nitpick\Enums\Transition;
use Packstub\Nitpick\Events\AccentChanged;
use Packstub\Nitpick\Events\PanelThemeChanged;
use Packstub\Nitpick\Events\ThemeChanged;
use Packstub\Nitpick\Filament\Forms\Components\AccentPicker;
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Support\Accent;
use Packstub\Nitpick\Support\PanelServing;
use Packstub\Nitpick\Support\RequestMemo;
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

    protected bool|Closure $hasAccent = false;

    /** Who may set the theme for everyone; null when nobody may (the store is never read). */
    protected bool|Closure|null $manage = null;

    /** @var class-string<Appearance> */
    protected string $pickerPage = Appearance::class;

    protected ?Closure $configurePicker = null;

    protected ?ThemeMode $forcedMode = null;

    protected bool $hasForcedMode = false;

    protected ?Panel $panel = null;

    /** The panel themes table is missing in this process (the migration not run): not asked again. */
    protected static bool $panelTableMissing = false;

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
     *     ->picker(accent: true)                                         // each user picks an accent colour too
     *     ->picker(manage: fn (): bool => auth()->user()->isAdmin())     // who sets the theme for everyone
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
        bool|Closure|null $accent = null,
        bool|Closure|null $manage = null,
    ): static {
        $this->hasPicker = true;
        $this->configurePicker = $using ?? $this->configurePicker;
        $this->pickerCondition = $condition ?? $this->pickerCondition;
        $this->pickerModal = $modal ?? $this->pickerModal;
        $this->userMenuItem = $userMenuItem ?? $this->userMenuItem;
        $this->pickerPage = $page ?? $this->pickerPage;
        $this->transition = $transition ?? $this->transition;
        $this->hasAccent = $accent ?? $this->hasAccent;
        $this->manage = $manage ?? $this->manage;

        return $this;
    }

    /**
     * Whether the signed-in user gets the picker: the Appearance page and its
     * user-menu item. While a manager has locked the theme for everyone,
     * only managers do, to change it.
     */
    public function hasPicker(): bool
    {
        if ($this->isLocked()) {
            return $this->canManage();
        }

        return $this->hasPicker && (bool) value($this->pickerCondition);
    }

    /**
     * Whether the signed-in user may pick a theme (and accent) of their own:
     * the picker is theirs and no manager has locked the theme for everyone.
     */
    public function hasUserChoice(): bool
    {
        return ! $this->isLocked() && $this->hasPicker && (bool) value($this->pickerCondition);
    }

    /**
     * Whether the signed-in user may set the theme for everyone in the panel,
     * or in the current tenant: ->picker(manage: ...).
     */
    public function canManage(): bool
    {
        return $this->hasPicker && $this->manage !== null && (bool) value($this->manage);
    }

    /**
     * What managers set for everyone in the current tenant, else in the
     * whole panel; null when nothing is set, or nobody may set it.
     */
    public function getPanelTheme(): ?PanelTheme
    {
        if ($this->manage === null) {
            return null;
        }

        $tenant = Filament::getTenant();

        return RequestMemo::current()->remember(
            "nitpick:panel-theme:{$this->getPanelId()}:".($tenant === null ? '' : $tenant->getMorphClass().':'.$tenant->getKey()),
            function () use ($tenant): ?PanelTheme {
                if (static::$panelTableMissing) {
                    return null;
                }

                try {
                    $store = $this->manager()->panelStore();
                    $own = $store->get($this->getPanelId(), $tenant);

                    // A tenant's setting falls back to the panel's one field at a time: a tenant
                    // that set only an accent keeps the theme and the lock set for the whole panel.
                    return $tenant === null ? $own : PanelTheme::over($own, $store->get($this->getPanelId(), null));
                } catch (QueryException $exception) {
                    // No table yet (the migration not run): the panel works as if nothing were set,
                    // and says so once per process instead of on every request.
                    static::$panelTableMissing = ! Schema::connection(ThemeManager::connectionName())->hasTable(ThemeManager::panelTableName());
                    report($exception);

                    return null;
                }
            },
        );
    }

    /**
     * Whether a manager locked the theme for everyone: users' own picks are
     * set aside and the picker is closed to all but managers.
     */
    public function isLocked(): bool
    {
        return $this->getPanelTheme()?->locked === true;
    }

    /**
     * Set the theme for everyone in the current tenant, or the whole panel;
     * null goes back to the panel's default in code.
     */
    public function setPanelTheme(string|Theme|null $theme): void
    {
        $id = null;

        if ($theme !== null) {
            $id = $this->manager()->resolve($theme)->getId();

            if (! isset($this->getThemes()[$id])) {
                throw new InvalidArgumentException("Theme [{$id}] is not offered on the [{$this->getPanelId()}] panel.");
            }
        }

        $this->updatePanelTheme(fn (PanelTheme $current): PanelTheme => $current->with(themeId: $id, clearTheme: $id === null));
    }

    /**
     * Set the accent colour for everyone in the current tenant, or the whole
     * panel; null goes back to the theme's own. Refused like a user's accent.
     */
    public function setPanelAccent(?string $accent): void
    {
        $accent = $accent === null ? null : strtolower($accent);

        if ($accent !== null && ($problem = Accent::problem($accent)) !== null) {
            throw new InvalidArgumentException(__($problem));
        }

        $this->updatePanelTheme(fn (PanelTheme $current): PanelTheme => $current->with(accent: $accent, clearAccent: $accent === null));
    }

    /**
     * Lock the theme (and accent) for everyone, or unlock it.
     */
    public function setPanelLocked(bool $locked): void
    {
        $this->updatePanelTheme(fn (PanelTheme $current): PanelTheme => $current->with(locked: $locked));
    }

    /**
     * @param  Closure(PanelTheme): PanelTheme  $change
     */
    protected function updatePanelTheme(Closure $change): void
    {
        if (! $this->canManage()) {
            throw new AuthorizationException("Only the [{$this->getPanelId()}] panel's managers may set its theme for everyone (->picker(manage: ...)).");
        }

        $tenant = Filament::getTenant();

        // On a tenant panel the setting belongs to a tenant: with none current (a page outside the
        // tenant's routes) the write would land on every tenant's fallback.
        if ($tenant === null && $this->panel?->hasTenancy()) {
            throw new AuthorizationException("The [{$this->getPanelId()}] panel's theme for everyone is set per tenant, and no tenant is current.");
        }

        $store = $this->manager()->panelStore();
        $theme = $change($store->get($this->getPanelId(), $tenant) ?? new PanelTheme);

        $store->set($this->getPanelId(), $tenant, $theme);
        RequestMemo::current()->forget("nitpick:panel-theme:{$this->getPanelId()}:");
        RequestMemo::current()->forget($this->activeThemeKey());
        RequestMemo::current()->forget("nitpick:accent:{$this->getPanelId()}:");

        event(new PanelThemeChanged($this->getPanelId(), $tenant, $theme));
    }

    /**
     * Whether users pick an accent colour too: the picker is on, accents are
     * turned on (->picker(accent: true)), and the store keeps them.
     */
    public function hasAccent(): bool
    {
        return $this->accentsOn() && $this->manager()->store() instanceof StoresAccents;
    }

    /**
     * Whether accent colours are turned on for this panel (->picker(accent: true)).
     */
    public function accentsOn(): bool
    {
        return $this->hasPicker && (bool) value($this->hasAccent);
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

    /**
     * The Appearance modal's and page's fields: the user's theme and accent,
     * and for managers the section that sets them for everyone. $columns
     * fixes the theme cards' columns (the modal's).
     *
     * @param  array<string, int> | null  $columns
     * @return array<int, Component | Field>
     */
    public function getPickerSchema(?array $columns = null): array
    {
        $cards = fn (ThemePicker $picker): ThemePicker => $columns === null ? $picker : $picker->columns($columns);
        $tenant = Filament::getTenant();

        return [
            $cards($this->configurePicker(ThemePicker::make('theme')->hiddenLabel()))
                ->visible(fn (): bool => $this->hasUserChoice()),
            AccentPicker::make('accent')
                ->visible(fn (): bool => $this->hasAccent() && $this->hasUserChoice()),
            Section::make(fn (): string => $tenant === null
                ? __('nitpick::themes.manage.everyone')
                : __('nitpick::themes.manage.tenant', ['tenant' => Filament::getTenantName($tenant)]))
                ->description(fn (): string => __($this->isLocked() ? 'nitpick::themes.manage.locked_description' : 'nitpick::themes.manage.description'))
                ->visible(fn (): bool => $this->canManage())
                ->schema([
                    $cards($this->configurePicker(ThemePicker::make('panel_theme')->forEveryone()->hiddenLabel())),
                    AccentPicker::make('panel_accent')->forEveryone()
                        ->visible(fn (): bool => $this->accentsOn()),
                    Toggle::make('panel_locked')
                        ->label(fn (): string => __('nitpick::themes.manage.lock'))
                        ->helperText(fn (): string => __('nitpick::themes.manage.lock_helper'))
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn (Toggle $component) => $component->state($this->isLocked()))
                        ->afterStateUpdated(function (Toggle $component, bool $state): void {
                            $this->setPanelLocked($state);
                            // The manager's own picker shows or hides, and their theme may change.
                            $component->getLivewire()->js('window.Nitpick ? window.Nitpick.reload() : window.location.reload()');
                        }),
                ]),
        ];
    }

    public function configurePicker(ThemePicker $picker): ThemePicker
    {
        if ($this->configurePicker !== null) {
            ($this->configurePicker)($picker);
        }

        return $picker;
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
            throw new LogicException('No themes are registered. Register one with Nitpick::register() or keep the bundled ones.');
        }

        return $themes;
    }

    /**
     * The theme users see until they pick one: what a manager set for the
     * tenant or the panel (->picker(manage: ...)), else the default in code.
     */
    public function getDefaultTheme(): Theme
    {
        $id = $this->getPanelTheme()?->themeId;

        return ($id === null ? null : ($this->getThemes()[$id] ?? null)) ?? $this->getCodeDefaultTheme();
    }

    /**
     * The default set in code: ->default(), else the config's, else the
     * first offered theme.
     */
    public function getCodeDefaultTheme(): Theme
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

        return RequestMemo::current()->remember(
            $this->activeThemeKey($user->getAuthIdentifier()),
            fn (): Theme => $this->getUserTheme($user) ?? $this->getDefaultTheme(),
        );
    }

    public function getUserTheme(?Authenticatable $user = null): ?Theme
    {
        if (! $this->hasUserChoice()) {
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
        RequestMemo::current()->forget($this->activeThemeKey());
        Cache::forget($this->failedThemeKey($user->getAuthIdentifier()));

        event(new ThemeChanged($user, $this->getPanelId(), $resolved));
    }

    /**
     * The signed-in user's accent colour on this panel: a Filament colour
     * name or a hex colour, or null for the theme's own.
     */
    public function getUserAccent(?Authenticatable $user = null): ?string
    {
        $user ??= $this->currentUser();

        if ($user === null || ! $this->hasAccent() || ! $this->hasUserChoice()) {
            return null;
        }

        return RequestMemo::current()->remember(
            "nitpick:accent:{$this->getPanelId()}:{$user->getAuthIdentifier()}",
            // A stored value is checked again, so one written around setUserAccent() is never served.
            function () use ($user): ?string {
                $accent = $this->accentStore()->getAccent($user, $this->getPanelId());

                return $accent !== null && Accent::problem($accent) === null ? $accent : null;
            },
        );
    }

    /**
     * Save a user's accent colour for this panel: a Filament colour name
     * ("indigo") or a hex colour ("#4f46e5"). Null clears it. A colour that
     * cannot be used is refused with an InvalidArgumentException whose
     * message says why, in the user's language.
     */
    public function setUserAccent(?string $accent, ?Authenticatable $user = null): void
    {
        $user ??= $this->currentUser();

        if ($user === null) {
            throw new LogicException('There is no signed-in user to save an accent colour for.');
        }

        if (! $this->hasAccent()) {
            throw new LogicException("Accent colours are off on the [{$this->getPanelId()}] panel: turn them on with ->picker(accent: true).");
        }

        $accent = $accent === null ? null : strtolower($accent);

        if ($accent !== null && ($problem = Accent::problem($accent)) !== null) {
            throw new InvalidArgumentException(__($problem));
        }

        $this->accentStore()->setAccent($user, $this->getPanelId(), $accent);
        RequestMemo::current()->forget("nitpick:accent:{$this->getPanelId()}:");

        event(new AccentChanged($user, $this->getPanelId(), $accent));
    }

    /**
     * The accent the panel uses for the signed-in user: their own, else the
     * one a manager set for everyone, else null (the theme's colour).
     */
    public function getAccent(): ?string
    {
        if (! $this->accentsOn()) {
            return null;
        }

        return $this->getUserAccent() ?? $this->getPanelTheme()?->accent;
    }

    /**
     * The CSS that applies the user's accent over any theme: its palette as
     * Filament's `--primary-*` variables, at a weight above the palettes and
     * theme stylesheets that set them. Empty without an accent.
     */
    public function accentCss(): string
    {
        $accent = $this->getAccent();

        if ($accent === null) {
            return '';
        }

        $variables = $this->manager()->cssVariables(['primary' => Accent::palette($accent)]);

        return ':root[data-nitpick-theme] { '.implode(' ', array_map(
            fn (string $name, string $value): string => "--{$name}: {$value};",
            array_keys($variables),
            $variables,
        )).' }';
    }

    /**
     * A theme's palette, with the colours it set on the panel while serving
     * this request, then the config and plugin overrides, applied.
     *
     * @return array<string, array<int | string, string | int> | string>
     */
    public function getColorsFor(Theme $theme): array
    {
        $accent = $this->getAccent();

        return [
            ...$theme->getColors(),
            ...$this->servingColors[$theme->getId()] ?? [],
            ...(array) config('nitpick.colors', []),
            ...$this->colors,
            ...($accent === null ? [] : ['primary' => Accent::palette($accent)]),
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
                    ->schema(fn (): array => $this->getPickerSchema(static::MODAL_COLUMNS))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(fn (): string => __('nitpick::themes.appearance.close'))
                    ->visible(fn (): bool => $this->hasPicker()),
            ]);
        }

        $panel->renderHook(PanelsRenderHook::HEAD_END, fn (): string => $this->renderHead());

        Event::listen(RequestHandled::class, function (RequestHandled $event): void {
            $this->rememberFailedTheme($event);

            // The request is over: the next one, on a server that keeps the app between requests, works
            // its users' themes out again (RequestMemo is scoped too, for jobs and Octane's own reset).
            RequestMemo::current()->forget($this->activeThemeKey());
        });
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

        RequestMemo::current()->put($this->activeThemeKey($user->getAuthIdentifier()), $default);

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

    /**
     * The memo key of a user's active theme on this panel; without a user,
     * the prefix of every user's.
     */
    protected function activeThemeKey(int|string|null $user = null): string
    {
        return "nitpick:active-theme:{$this->getPanelId()}:".($user ?? '');
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
            sprintf('<meta name="nitpick-theme" content="%s">', e($theme->getId())),
        ];

        // Before the theme's stylesheets, so a stylesheet that sets a colour variable itself wins.
        if ($selectable) {
            $tags[] = $this->renderPaletteStyles();
        }

        foreach ($this->stylesheetHrefs($theme) as $index => $href) {
            $tags[] = sprintf(
                '<link rel="stylesheet"%s href="%s" data-nitpick-theme="%s" data-nitpick-theme-stylesheet data-navigate-track>',
                $index === 0 ? ' id="nitpick-theme-active"' : '',
                e($href),
                e($theme->getId()),
            );
        }

        if ($custom = $this->getCustomCss()) {
            $href = filter_var($custom, FILTER_VALIDATE_URL) ? $custom : asset(ltrim($custom, '/'));

            $tags[] = sprintf('<link rel="stylesheet" id="nitpick-theme-custom" href="%s" data-navigate-track>', e($href));
        }

        // The user's accent, after the theme's stylesheets so it wins; there even when empty, so a change
        // made from the picker can fill it in place (with the nonce it was rendered with).
        if ($this->accentsOn()) {
            $tags[] = '<style id="nitpick-accent"'.$this->nonceAttribute().'>'.$this->accentCss().'</style>';
        }

        // The cover a transition paints over the panel, set before the body paints so a page that
        // a pick reloaded can start under it.
        if ($selectable) {
            $tags[] = '<style id="nitpick-cover"'.$this->nonceAttribute().'>html[data-nitpick-cover]::after { content: ""; position: fixed; inset: 0; z-index: 2147483647; pointer-events: none; background: var(--nitpick-cover-color, #000); } #nitpick-cover-layer { position: fixed; inset: 0; z-index: 2147483647; pointer-events: none; overflow: hidden; } #nitpick-cover-layer > div { position: absolute; inset: 0; will-change: transform, opacity; } html[data-nitpick-still] *, html[data-nitpick-still] *::before, html[data-nitpick-still] *::after { transition: none !important; }</style>';
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

            $blocks[] = sprintf('[data-nitpick-theme="%s"] { %s }', e($id), $declarations);
        }

        if ($blocks === []) {
            return '';
        }

        return '<style id="nitpick-palettes"'.$this->nonceAttribute().'>'.implode("\n", $blocks).'</style>';
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
     * The few lines that must run before the body paints, inline: the active
     * theme stamped on <html>, and a page that a pick reloaded started under
     * the transition's cover (a note in sessionStorage). Then the manifest of
     * offered themes, as JSON, and the script that does the rest and exposes
     * window.Nitpick (resources/js/nitpick.js, a Filament asset published by
     * `php artisan filament:assets`, cached like any other). Nothing here
     * writes Filament's light/dark localStorage key on load: the user's
     * light/dark toggle is theirs; only a pick adopts a theme's mode.
     *
     * The inline script and styles carry the app's CSP nonce when it sets
     * one (Vite::useCspNonce()).
     *
     * @param  array{themes: array<string, array{hrefs: array<int, string>, label: string, mode: string | null, reload: bool}>, transition: string | null} | null  $manifest
     */
    protected function renderBootstrapScript(Theme $theme, ?array $manifest): string
    {
        $id = json_encode($theme->getId());
        $nonce = $this->nonceAttribute();
        $tags = [];

        $tags[] = <<<HTML
            <script{$nonce} data-navigate-once>
            (function () {
                var html = document.documentElement, meta = document.querySelector('meta[name="nitpick-theme"]');
                html.setAttribute('data-nitpick-theme', meta ? meta.getAttribute('content') : {$id});
                try {
                    var note = JSON.parse(sessionStorage.getItem('nitpick:cover') || 'null');
                    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    if (note && note.transition && ! still && Date.now() - note.at < 10000) {
                        html.style.setProperty('--nitpick-cover-color', note.color);
                        html.setAttribute('data-nitpick-cover', '');
                        html.setAttribute('data-nitpick-still', '');
                        // Never leave the panel covered, even when the script below cannot load.
                        setTimeout(function () { if (! window.Nitpick) { html.removeAttribute('data-nitpick-cover'); html.removeAttribute('data-nitpick-still'); } }, 4000);
                    }
                } catch (e) {}
            })();
            </script>
            HTML;

        if ($manifest !== null) {
            $tags[] = '<script type="application/json" id="nitpick-manifest">'.json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP).'</script>';
        }

        $tags[] = sprintf(
            '<script src="%s" defer data-navigate-once%s></script>',
            e(FilamentAsset::getScriptSrc('nitpick', package: ThemeManager::ASSET_PACKAGE)),
            $nonce,
        );

        return implode("\n", $tags);
    }

    /**
     * ` nonce="…"` for an inline tag, when the app sets a CSP nonce.
     */
    protected function nonceAttribute(): string
    {
        $nonce = Vite::cspNonce();

        return filled($nonce) ? sprintf(' nonce="%s"', e($nonce)) : '';
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

    protected function accentStore(): StoresAccents
    {
        $store = $this->manager()->store();

        if (! $store instanceof StoresAccents) {
            throw new LogicException($store::class.' keeps no accent colours: implement '.StoresAccents::class.'.');
        }

        return $store;
    }

    protected function manager(): ThemeManager
    {
        return app(ThemeManager::class);
    }
}
