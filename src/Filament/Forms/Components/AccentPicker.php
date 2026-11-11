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
            $component->state(NitpickPlugin::get()->getUserAccent() ?? static::THEME);
        });

        $this->afterStateUpdated(function (AccentPicker $component, ?string $state, ?string $old): void {
            $plugin = NitpickPlugin::get();

            try {
                $plugin->setUserAccent(blank($state) ? null : $state);
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
