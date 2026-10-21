<?php

namespace Packstub\Nitpick\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;
use Packstub\Nitpick\NitpickPlugin;

/**
 * Where a user picks the theme they see in this panel. Registered by the
 * plugin when ->userSelectable() is on and linked from the user menu.
 */
class Appearance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'appearance';

    protected string $view = 'nitpick::pages.appearance';

    /** @var array<string, mixed> | null */
    public ?array $data = [];

    public static function getLabel(): string
    {
        return __('nitpick::themes.appearance.title');
    }

    public function getTitle(): string
    {
        return static::getLabel();
    }

    public function getSubheading(): ?string
    {
        return __('nitpick::themes.appearance.subheading');
    }

    public static function canAccess(): bool
    {
        return parent::canAccess() && NitpickPlugin::get()->isUserSelectable();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                NitpickPlugin::get()->configurePicker(ThemePicker::make('theme')->hiddenLabel()),
            ])
            ->statePath('data');
    }
}
