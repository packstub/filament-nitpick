<?php

namespace Packstub\Themes\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Packstub\Themes\Contracts\Theme;
use Packstub\Themes\ThemesPlugin;

/**
 * A card-per-theme radio for the themes the current panel offers. It fills
 * itself with the user's current theme and, by default, saves the choice
 * the moment it changes, so it works on the bundled Appearance page and
 * dropped into your own profile form alike.
 */
class ThemePicker extends Field
{
    protected string $view = 'packstub-themes::forms.theme-picker';

    protected bool|Closure $shouldPersist = true;

    protected bool|Closure $shouldNotify = true;

    public static function make(?string $name = 'theme'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => __('packstub-themes::themes.picker.label'));
        $this->helperText(fn (): string => __('packstub-themes::themes.picker.helper'));
        $this->live();
        $this->dehydrated(false);

        $this->afterStateHydrated(function (ThemePicker $component): void {
            $component->state($component->getPlugin()->getActiveTheme()->getId());
        });

        $this->afterStateUpdated(function (ThemePicker $component, ?string $state): void {
            if (! $component->shouldPersist()) {
                return;
            }

            $component->getPlugin()->setUserTheme($state);

            if ($component->shouldNotify()) {
                Notification::make()
                    ->title(__('packstub-themes::themes.notifications.saved'))
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

    public function getPlugin(): ThemesPlugin
    {
        return ThemesPlugin::get();
    }

    /**
     * @return array<string, Theme>
     */
    public function getThemes(): array
    {
        return $this->getPlugin()->getThemes();
    }
}
