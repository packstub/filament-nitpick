<?php

use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Enums\Transition;
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\Ocean;
use Packstub\Nitpick\Themes\Aurora;
use Packstub\Nitpick\Themes\Midnight;

class CustomAppearance extends Appearance
{
    protected static ?string $slug = 'my-appearance';
}

/**
 * The user-menu items the panel was given, without building Filament's own (they need a user).
 *
 * @return array<string, mixed>
 */
function userMenuItemsOf(Panel $panel): array
{
    return array_merge(...(fn (): array => $this->userMenuItemGroups)->call($panel));
}

function registerOn(NitpickPlugin $plugin, string $id = 'test'): Panel
{
    $panel = Panel::make()->id($id);

    $plugin->register($panel);

    return $panel;
}

it('has a stable plugin id', function (): void {
    expect(NitpickPlugin::make()->getId())->toBe('nitpick');
});

it('applies the config default theme palette to the panel', function (): void {
    $panel = registerOn(NitpickPlugin::make());

    expect($panel->getColors()['primary'])->toBe(Color::Sky);
});

it('accepts a default theme by class, id, instance or closure', function (): void {
    expect(registerOn(NitpickPlugin::make()->default(Midnight::class))->getColors()['primary'])->toBe(Color::Violet)
        ->and(registerOn(NitpickPlugin::make()->default('midnight'))->getColors()['primary'])->toBe(Color::Violet)
        ->and(registerOn(NitpickPlugin::make()->default(new Ocean))->getColors()['primary'])->toBe(Color::Teal)
        ->and(registerOn(NitpickPlugin::make()->default(fn (): string => 'ocean'))->getColors()['primary'])->toBe(Color::Teal);
});

it('falls back to the first offered theme when the config default is not offered', function (): void {
    $panel = registerOn(NitpickPlugin::make()->themes([Midnight::class, Ocean::class]));

    expect($panel->getColors()['primary'])->toBe(Color::Violet);
});

it('offers only the listed themes', function (): void {
    $plugin = NitpickPlugin::make()->themes(['midnight', Ocean::class]);

    expect(array_keys($plugin->getThemes()))->toBe(['midnight', 'ocean']);
});

it('throws when the explicit default is not offered on the panel', function (): void {
    registerOn(NitpickPlugin::make()->themes([Midnight::class])->default(Aurora::class))->getColors();
})->throws(InvalidArgumentException::class, 'not offered');

it('throws when an offered theme is unknown', function (): void {
    NitpickPlugin::make()->themes(['nope'])->getThemes();
})->throws(InvalidArgumentException::class, 'not registered');

it('layers colour overrides on the theme palette', function (): void {
    config()->set('nitpick.colors', ['primary' => Color::Rose, 'warning' => Color::Orange]);

    $panel = registerOn(NitpickPlugin::make()->default(Midnight::class)->colors(['primary' => Color::Indigo]));
    $colors = $panel->getColors();

    expect($colors['primary'])->toBe(Color::Indigo)
        ->and($colors['warning'])->toBe(Color::Orange)
        ->and($colors['danger'])->toBe(Color::Pink);
});

it('keeps each panel on its own theme', function (): void {
    $admin = registerOn(NitpickPlugin::make()->default(Aurora::class), 'admin-2');
    $customer = registerOn(NitpickPlugin::make()->default(Midnight::class), 'customer-2');

    expect($admin->getColors()['primary'])->toBe(Color::Sky)
        ->and($customer->getColors()['primary'])->toBe(Color::Violet);
});

it('sets the panel default mode from the theme preference at boot', function (): void {
    $aurora = NitpickPlugin::make()->default(Aurora::class);
    $midnight = NitpickPlugin::make()->default(Midnight::class);
    $ocean = NitpickPlugin::make()->default(Ocean::class);

    $auroraPanel = registerOn($aurora, 'a');
    $midnightPanel = registerOn($midnight, 'b');
    $oceanPanel = registerOn($ocean, 'c');

    $aurora->boot($auroraPanel);
    $midnight->boot($midnightPanel);
    $ocean->boot($oceanPanel);

    expect($auroraPanel->getDefaultThemeMode())->toBe(ThemeMode::Light)
        ->and($midnightPanel->getDefaultThemeMode())->toBe(ThemeMode::Dark)
        ->and($oceanPanel->getDefaultThemeMode())->toBe(ThemeMode::System);
});

it('lets a forced mode override the theme preference', function (): void {
    $plugin = NitpickPlugin::make()->default(Midnight::class)->forceMode(ThemeMode::Light);
    $panel = registerOn($plugin);
    $plugin->boot($panel);

    expect($panel->getDefaultThemeMode())->toBe(ThemeMode::Light);

    $plugin = NitpickPlugin::make()->default(Midnight::class)->forceMode(null);
    $panel = registerOn($plugin, 'null-mode');
    $plugin->boot($panel);

    expect($panel->getDefaultThemeMode())->toBe(ThemeMode::System);
});

it('reads a forced mode from config as a string', function (): void {
    config()->set('nitpick.force_mode', 'light');

    $plugin = NitpickPlugin::make()->default(Midnight::class);
    $panel = registerOn($plugin);
    $plugin->boot($panel);

    expect($panel->getDefaultThemeMode())->toBe(ThemeMode::Light);
});

it('resolves the custom stylesheet from the plugin, then config', function (): void {
    expect(NitpickPlugin::make()->getCustomCss())->toBeNull();

    config()->set('nitpick.custom_css', 'css/from-config.css');

    expect(NitpickPlugin::make()->getCustomCss())->toBe('css/from-config.css')
        ->and(NitpickPlugin::make()->customCss('css/mine.css')->getCustomCss())->toBe('css/mine.css')
        ->and(NitpickPlugin::make()->customCss(null)->getCustomCss())->toBeNull();
});

it('turns the picker on with picker(), and keeps what an earlier call set', function (): void {
    expect(NitpickPlugin::make()->hasPicker())->toBeFalse()
        ->and(NitpickPlugin::make()->picker()->hasPicker())->toBeTrue()
        ->and(NitpickPlugin::make()->picker(condition: fn (): bool => false)->hasPicker())->toBeFalse();

    $plugin = NitpickPlugin::make()
        ->picker(condition: fn (): bool => false, modal: false, page: CustomAppearance::class, transition: Transition::Sweep)
        ->picker(fn (ThemePicker $picker) => $picker->descriptions());

    expect($plugin->hasPicker())->toBeFalse()
        ->and($plugin->hasPickerModal())->toBeFalse()
        ->and($plugin->getPickerPage())->toBe(CustomAppearance::class)
        ->and($plugin->getTransition())->toBe(Transition::Sweep)
        ->and($plugin->configurePicker(ThemePicker::make('theme'))->hasDescriptions())->toBeTrue();
});

it('registers the picker page and leaves the user-menu item out on request', function (): void {
    $panel = registerOn(NitpickPlugin::make()->picker(userMenuItem: false, page: CustomAppearance::class));

    expect($panel->getPages())->toContain(CustomAppearance::class)
        ->and(userMenuItemsOf($panel))->not->toHaveKey('nitpick-appearance')
        ->and(userMenuItemsOf(registerOn(NitpickPlugin::make()->picker())))->toHaveKey('nitpick-appearance');
});

it('turns the transition off with false, from the picker or the config', function (): void {
    expect(NitpickPlugin::make()->picker(transition: false)->getTransition())->toBeNull()
        ->and(NitpickPlugin::make()->picker()->getTransition())->toBe(Transition::Fade);

    config()->set('nitpick.transition', null);

    expect(NitpickPlugin::make()->picker()->getTransition())->toBeNull()
        ->and(NitpickPlugin::make()->picker(transition: 'slide')->getTransition())->toBe(Transition::Slide);
});

it('keeps the beta names working: userSelectable(), appearancePage() and their getters', function (): void {
    $plugin = NitpickPlugin::make()->userSelectable()->appearancePage(CustomAppearance::class);

    expect($plugin->isUserSelectable())->toBeTrue()
        ->and($plugin->hasPicker())->toBeTrue()
        ->and($plugin->getAppearancePage())->toBe(CustomAppearance::class)
        ->and(NitpickPlugin::make()->userSelectable(false)->hasPicker())->toBeFalse();

    expect(userMenuItemsOf(registerOn(NitpickPlugin::make()->userSelectable(userMenuItem: false))))->not->toHaveKey('nitpick-appearance');
});
