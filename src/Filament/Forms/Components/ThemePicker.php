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
 */
class ThemePicker extends Field
{
    protected string $view = 'nitpick::forms.theme-picker';

    protected bool|Closure $shouldPersist = true;

    protected bool|Closure $shouldNotify = false;

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
            $component->state($component->getPlugin()->getActiveTheme()->getId());
        });

        $this->afterStateUpdated(function (ThemePicker $component, ?string $state, ?string $old): void {
            if (! $component->shouldPersist()) {
                return;
            }

            $component->getPlugin()->setUserTheme($state);

            // A theme that configures the panel (top navigation, plugins) shows only after a page load.
            if ($component->needsReload($old) || $component->needsReload($state)) {
                $component->getLivewire()->js('window.location.reload()');
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
        $theme = $theme === null ? null : app(ThemeManager::class)->find($theme);

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
