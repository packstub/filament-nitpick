<?php

use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Packstub\Themes\Tests\Fixtures\Ocean;
use Packstub\Themes\Themes\Aurora;
use Packstub\Themes\Themes\Midnight;
use Packstub\Themes\ThemesPlugin;

function registerOn(ThemesPlugin $plugin, string $id = 'test'): Panel
{
    $panel = Panel::make()->id($id);

    $plugin->register($panel);

    return $panel;
}

it('has a stable plugin id', function (): void {
    expect(ThemesPlugin::make()->getId())->toBe('packstub-themes');
});

it('applies the config default theme palette to the panel', function (): void {
    $panel = registerOn(ThemesPlugin::make());

    expect($panel->getColors()['primary'])->toBe(Color::Sky);
});

it('accepts a default theme by class, id, instance or closure', function (): void {
    expect(registerOn(ThemesPlugin::make()->default(Midnight::class))->getColors()['primary'])->toBe(Color::Violet)
        ->and(registerOn(ThemesPlugin::make()->default('midnight'))->getColors()['primary'])->toBe(Color::Violet)
        ->and(registerOn(ThemesPlugin::make()->default(new Ocean))->getColors()['primary'])->toBe(Color::Teal)
        ->and(registerOn(ThemesPlugin::make()->default(fn (): string => 'ocean'))->getColors()['primary'])->toBe(Color::Teal);
});

it('falls back to the first offered theme when the config default is not offered', function (): void {
    $panel = registerOn(ThemesPlugin::make()->themes([Midnight::class, Ocean::class]));

    expect($panel->getColors()['primary'])->toBe(Color::Violet);
});

it('offers only the listed themes', function (): void {
    $plugin = ThemesPlugin::make()->themes(['midnight', Ocean::class]);

    expect(array_keys($plugin->getThemes()))->toBe(['midnight', 'ocean']);
});

it('throws when the explicit default is not offered on the panel', function (): void {
    registerOn(ThemesPlugin::make()->themes([Midnight::class])->default(Aurora::class))->getColors();
})->throws(InvalidArgumentException::class, 'not offered');

it('throws when an offered theme is unknown', function (): void {
    ThemesPlugin::make()->themes(['nope'])->getThemes();
})->throws(InvalidArgumentException::class, 'not registered');

it('layers colour overrides on the theme palette', function (): void {
    config()->set('packstub-themes.colors', ['primary' => Color::Rose, 'warning' => Color::Orange]);

    $panel = registerOn(ThemesPlugin::make()->default(Midnight::class)->colors(['primary' => Color::Indigo]));
    $colors = $panel->getColors();

    expect($colors['primary'])->toBe(Color::Indigo)
        ->and($colors['warning'])->toBe(Color::Orange)
        ->and($colors['danger'])->toBe(Color::Pink);
});

it('keeps each panel on its own theme', function (): void {
    $admin = registerOn(ThemesPlugin::make()->default(Aurora::class), 'admin-2');
    $customer = registerOn(ThemesPlugin::make()->default(Midnight::class), 'customer-2');

    expect($admin->getColors()['primary'])->toBe(Color::Sky)
        ->and($customer->getColors()['primary'])->toBe(Color::Violet);
});

it('sets the panel default mode from the theme preference at boot', function (): void {
    $aurora = ThemesPlugin::make()->default(Aurora::class);
    $midnight = ThemesPlugin::make()->default(Midnight::class);
    $ocean = ThemesPlugin::make()->default(Ocean::class);

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
    $plugin = ThemesPlugin::make()->default(Midnight::class)->forceMode(ThemeMode::Light);
    $panel = registerOn($plugin);
    $plugin->boot($panel);

    expect($panel->getDefaultThemeMode())->toBe(ThemeMode::Light);

    $plugin = ThemesPlugin::make()->default(Midnight::class)->forceMode(null);
    $panel = registerOn($plugin, 'null-mode');
    $plugin->boot($panel);

    expect($panel->getDefaultThemeMode())->toBe(ThemeMode::System);
});

it('reads a forced mode from config as a string', function (): void {
    config()->set('packstub-themes.force_mode', 'light');

    $plugin = ThemesPlugin::make()->default(Midnight::class);
    $panel = registerOn($plugin);
    $plugin->boot($panel);

    expect($panel->getDefaultThemeMode())->toBe(ThemeMode::Light);
});

it('resolves the custom stylesheet from the plugin, then config', function (): void {
    expect(ThemesPlugin::make()->getCustomCss())->toBeNull();

    config()->set('packstub-themes.custom_css', 'css/from-config.css');

    expect(ThemesPlugin::make()->getCustomCss())->toBe('css/from-config.css')
        ->and(ThemesPlugin::make()->customCss('css/mine.css')->getCustomCss())->toBe('css/mine.css')
        ->and(ThemesPlugin::make()->customCss(null)->getCustomCss())->toBeNull();
});
