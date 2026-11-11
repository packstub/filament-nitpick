<?php

namespace Packstub\Nitpick\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use InvalidArgumentException;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Support\Accent;

/**
 * The user's accent colour: the theme's own, one of Filament's colours, or
 * any colour from the browser's colour picker. Saved the moment it changes
 * and applied to the page at once. Shown under the theme cards in the
 * Appearance modal and page when ->picker(accent: true) is on; drop it into
 * a profile form of your own the same way.
 */
class AccentPicker extends Field
{
    public const THEME = '';

    protected string $view = 'nitpick::forms.accent-picker';

    protected bool $isForEveryone = false;

    public static function make(?string $name = 'accent'): static
    {
        return parent::make($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => __('nitpick::themes.accent.label'));
        $this->live();
        $this->dehydrated(false);

        $this->afterStateHydrated(function (AccentPicker $component): void {
            $plugin = NitpickPlugin::get();

            $component->state(($component->isForEveryone() ? $plugin->getPanelTheme()?->accent : $plugin->getUserAccent()) ?? static::THEME);
        });

        $this->afterStateUpdated(function (AccentPicker $component, ?string $state, ?string $old): void {
            $plugin = NitpickPlugin::get();

            try {
                $component->isForEveryone()
                    ? $plugin->setPanelAccent(blank($state) ? null : $state)
                    : $plugin->setUserAccent(blank($state) ? null : $state);
            } catch (InvalidArgumentException $exception) {
                $component->state($old ?? static::THEME);

                Notification::make()
                    ->danger()
                    ->title(__('nitpick::themes.accent.refused'))
                    ->body($exception->getMessage())
                    ->send();

                return;
            }

            $component->getLivewire()->js('window.Nitpick && window.Nitpick.accent('.json_encode($plugin->accentCss(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).')');
        });
    }

    /**
     * Pick the accent for everyone in the panel (or the current tenant)
     * instead of the user's own.
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
     * The first option: the theme's own colour, or, for a user whose manager
     * set an accent for everyone, that one.
     */
    public function getDefaultLabel(): string
    {
        return ! $this->isForEveryone() && NitpickPlugin::get()->getPanelTheme()?->accent !== null
            ? __('nitpick::themes.accent.panel')
            : __('nitpick::themes.accent.theme');
    }

    /**
     * @return array<string, string> Filament's colours offered as swatches, by name, as CSS colours.
     */
    public function getSwatches(): array
    {
        return array_map(fn (array $palette): string => $palette[500], Accent::swatches());
    }

    public function isCustom(?string $state): bool
    {
        return filled($state) && str_starts_with($state, '#');
    }
}
