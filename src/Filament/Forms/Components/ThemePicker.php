<?php

namespace Packstub\Nitpick\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Packstub\Nitpick\Contracts\Theme;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Support\ThemePreview;
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

    protected bool|Closure $hasPreviews = true;

    protected bool $isForEveryone = false;

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

            if ($component->isForEveryone()) {
                $id = $plugin->getPanelTheme()?->themeId;
                $component->state($id !== null && isset($component->getThemes()[$id]) ? $id : static::PANEL_DEFAULT);

                return;
            }

            $component->state($component->hasDefaultOption()
                ? ($plugin->getUserTheme()?->getId() ?? static::PANEL_DEFAULT)
                : $plugin->getActiveTheme()->getId());
        });

        $this->afterStateUpdated(function (ThemePicker $component, ?string $state, ?string $old): void {
            if (! $component->shouldPersist()) {
                return;
            }

            if ($component->isForEveryone()) {
                $component->saveForEveryone($state);

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

    /**
     * Pick the theme for everyone in the panel (or the current tenant)
     * instead of the user's own: what managers see under "For everyone".
     * "Panel default" then stands for the default set in code.
     */
    public function forEveryone(bool $condition = true): static
    {
        $this->isForEveryone = $condition;

        return $this;
    }

    public function isForEveryone(): bool
    {
        return $this->isForEveryone;
    }

    /**
     * Saves a manager's pick for everyone, and shows its effect on their own
     * panel: none while they have a theme of their own, else a swap (or a
     * reload, for a theme that configures the panel).
     */
    protected function saveForEveryone(?string $state): void
    {
        $plugin = $this->getPlugin();
        $manager = app(ThemeManager::class);
        $before = $plugin->getActiveTheme();

        $plugin->setPanelTheme($state === static::PANEL_DEFAULT ? null : $state);

        $after = $plugin->getActiveTheme();

        if ($after->getId() === $before->getId()) {
            return;
        }

        $this->getLivewire()->js($manager->configuresPanel($before) || $manager->configuresPanel($after)
            ? 'window.Nitpick ? window.Nitpick.reload() : window.location.reload()'
            : 'window.Nitpick && window.Nitpick.pick('.json_encode($after->getId()).')');
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

        if ($this->hasDefaultOption() || $this->isForEveryone()) {
            $default = $this->isForEveryone() ? $this->getPlugin()->getCodeDefaultTheme() : $this->getPlugin()->getDefaultTheme();

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

    /**
     * Show a small preview of each theme in light and dark on its card,
     * instead of its swatches. On by default; a theme whose colours are
     * unknown keeps its swatches.
     */
    public function previews(bool|Closure $condition = true): static
    {
        $this->hasPreviews = $condition;

        return $this;
    }

    public function hasPreviews(): bool
    {
        return (bool) $this->evaluate($this->hasPreviews);
    }

    /**
     * A theme's preview on this panel, with the colours the panel would
     * give it (its overrides included), or null without one.
     *
     * @return array{light: array<string, string>, dark: array<string, string>} | null
     */
    public function getPreview(Theme $theme): ?array
    {
        if (! $this->hasPreviews()) {
            return null;
        }

        $plugin = $this->getPlugin();

        return ThemePreview::for($theme, $plugin->getColorsFor($theme), accent: $plugin->getUserAccent() !== null);
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
            static::PANEL_DEFAULT => $this->isForEveryone() ? $this->getPlugin()->getCodeDefaultTheme() : $this->getPlugin()->getDefaultTheme(),
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
