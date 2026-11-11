<?php

namespace Packstub\Nitpick\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Packstub\Nitpick\Contracts\Theme;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\ThemeManager;

/**
 * A card-per-theme radio for the themes the current panel offers. It fills
 * itself with the user's current theme and, by default, saves the choice
 * the moment it changes (quietly: the new look is the confirmation), so it works on the bundled Appearance page and
 * dropped into your own profile form alike.
 *
 * The cards fill the space they have unless ->columns() fixes them, in the
 * shape of Radio::columns(): ['default' => 1, 'sm' => 2, 'lg' => 4]. Fixed
 * columns keep the cards in place while a theme changes the layout.
 */
class ThemePicker extends Field
{
    /**
     * The value of the "Panel default" card: no choice of the user's own, so
     * they follow the panel's default theme. A theme id never starts with an
     * underscore, so the two never collide.
     */
    public const PANEL_DEFAULT = '_default';

    protected string $view = 'nitpick::forms.theme-picker';

    protected bool|Closure $shouldPersist = true;

    protected bool|Closure $shouldNotify = false;

    protected bool|Closure $hasDescriptions = false;

    protected bool|Closure $hasSwatches = true;

    protected bool|Closure $hasDefaultOption = true;

    public static function make(?string $name = 'theme'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => __('nitpick::themes.picker.label'));
        $this->helperText(fn (): string => __('nitpick::themes.picker.helper'));
        $this->live();
        $this->dehydrated(false);

        $this->afterStateHydrated(function (ThemePicker $component): void {
            $plugin = $component->getPlugin();

            $component->state($component->hasDefaultOption()
                ? ($plugin->getUserTheme()?->getId() ?? static::PANEL_DEFAULT)
                : $plugin->getActiveTheme()->getId());
        });

        $this->afterStateUpdated(function (ThemePicker $component, ?string $state, ?string $old): void {
            if (! $component->shouldPersist()) {
                return;
            }

            $component->getPlugin()->setUserTheme($state === static::PANEL_DEFAULT ? null : $state);

            // A theme that configures the panel (top navigation, plugins) shows only after a page load,
            // which waits for the transition's cover.
            if ($component->needsReload($old) || $component->needsReload($state)) {
                $component->getLivewire()->js('window.Nitpick ? window.Nitpick.reload() : window.location.reload()');
            }

            if ($component->shouldNotify()) {
                Notification::make()
                    ->title(__('nitpick::themes.notifications.saved'))
                    ->success()
                    ->send();
            }
        });
    }

    /**
     * Whether a change is saved immediately. Turn it off to handle the
     * value yourself from the form's submit.
     */
    public function persist(bool|Closure $condition = true): static
    {
        $this->shouldPersist = $condition;

        return $this;
    }

    /**
     * Send a "Theme updated" notification after a saved change. Off by
     * default: the panel changing its look is the confirmation, and a user
     * trying a few themes would collect a stack of them.
     */
    public function notify(bool|Closure $condition = true): static
    {
        $this->shouldNotify = $condition;

        return $this;
    }

    /**
     * Show each theme's description under its name. Off by default: the
     * name and swatches tell themes apart, and short cards scan faster; the
     * description is still the card's tooltip.
     */
    public function descriptions(bool|Closure $condition = true): static
    {
        $this->hasDescriptions = $condition;

        return $this;
    }

    /**
     * Show each theme's swatches above its name. On by default; a theme
     * whose colours are unknown shows empty ones, so the cards line up.
     */
    public function swatches(bool|Closure $condition = true): static
    {
        $this->hasSwatches = $condition;

        return $this;
    }

    /**
     * Offer a first card, "Panel default", that clears the user's choice, so
     * they follow the panel's default theme again, including when it changes
     * later. On by default.
     */
    public function defaultOption(bool|Closure $condition = true): static
    {
        $this->hasDefaultOption = $condition;

        return $this;
    }

    public function hasDefaultOption(): bool
    {
        return (bool) $this->evaluate($this->hasDefaultOption);
    }

    /**
     * The cards, in order: "Panel default" when it is offered, then each
     * theme. `value` is the field's state, `theme` the theme it shows.
     *
     * @return array<int, array{value: string, theme: Theme, label: string, hint: string | null}>
     */
    public function getOptions(): array
    {
        $options = [];

        if ($this->hasDefaultOption()) {
            $default = $this->getPlugin()->getDefaultTheme();

            $options[] = [
                'value' => static::PANEL_DEFAULT,
                'theme' => $default,
                'label' => __('nitpick::themes.picker.default'),
                'hint' => __('nitpick::themes.picker.default_hint', ['theme' => $default->getLabel()]),
            ];
        }

        foreach ($this->getThemes() as $id => $theme) {
            $options[] = ['value' => (string) $id, 'theme' => $theme, 'label' => $theme->getLabel(), 'hint' => null];
        }

        return $options;
    }

    public function hasDescriptions(): bool
    {
        return (bool) $this->evaluate($this->hasDescriptions);
    }

    public function hasSwatches(): bool
    {
        return (bool) $this->evaluate($this->hasSwatches);
    }

    public function shouldPersist(): bool
    {
        return (bool) $this->evaluate($this->shouldPersist);
    }

    public function shouldNotify(): bool
    {
        return (bool) $this->evaluate($this->shouldNotify);
    }

    protected function needsReload(?string $theme): bool
    {
        $theme = match ($theme) {
            null => null,
            static::PANEL_DEFAULT => $this->getPlugin()->getDefaultTheme(),
            default => app(ThemeManager::class)->find($theme),
        };

        return $theme !== null && app(ThemeManager::class)->configuresPanel($theme);
    }

    public function getPlugin(): NitpickPlugin
    {
        return NitpickPlugin::get();
    }

    /**
     * @return array<string, Theme>
     */
    public function getThemes(): array
    {
        return $this->getPlugin()->getThemes();
    }
}
