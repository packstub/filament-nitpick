<?php

use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\Ocean;
use Packstub\Nitpick\Themes\Aurora;
use Packstub\Nitpick\Themes\Midnight;

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
