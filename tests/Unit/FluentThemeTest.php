<?php

use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Packstub\Themes\Contracts\Theme as ThemeContract;
use Packstub\Themes\Facades\Themes;
use Packstub\Themes\Theme;
use Packstub\Themes\ThemeManager;
use Packstub\Themes\ThemesPlugin;

it('makes a palette-only theme from an id and its colours', function (): void {
    $theme = Theme::make('deep-forest')->colors(['primary' => Color::Emerald]);

    expect($theme)->toBeInstanceOf(ThemeContract::class)
        ->and($theme->getId())->toBe('deep-forest')
        ->and($theme->getLabel())->toBe('Deep Forest')
        ->and($theme->getDescription())->toBeNull()
        ->and($theme->getColors())->toBe(['primary' => Color::Emerald])
        ->and($theme->getPreferredMode())->toBeNull()
        ->and($theme->getStylesheetPath())->toBeNull()
        ->and($theme->getSwatches())->toBe([Color::Emerald[500]]);
});

it('takes a label, description, stylesheet, mode and swatches', function (): void {
    $theme = Theme::make('nord')
        ->label('Nord (Arctic)')
        ->description('A north-bluish palette.')
        ->stylesheet('/themes/nord.css')
        ->mode(ThemeMode::Dark)
        ->swatches(['#5e81ac', '#88c0d0']);

    expect($theme->getLabel())->toBe('Nord (Arctic)')
        ->and($theme->getDescription())->toBe('A north-bluish palette.')
        ->and($theme->getStylesheetPath())->toBe('/themes/nord.css')
        ->and($theme->getPreferredMode())->toBe(ThemeMode::Dark)
        ->and($theme->getSwatches())->toBe(['#5e81ac', '#88c0d0']);
});

it('registers like any other theme, stylesheet included', function (): void {
    Themes::register(
        Theme::make('nord')
            ->colors(['primary' => Color::Indigo])
            ->stylesheet(__DIR__.'/../../resources/css/themes/midnight.css'),
    );

    $panel = Panel::make()->id('test');
    ThemesPlugin::make()->default('nord')->register($panel);

    expect(Themes::find('nord')->getLabel())->toBe('Nord')
        ->and($panel->getColors()['primary'])->toBe(Color::Indigo)
        ->and(FilamentAsset::getStyleHref('packstub-theme-nord', package: ThemeManager::ASSET_PACKAGE))
        ->toContain('packstub-theme-nord.css');
});

it('still validates the id on registration', function (): void {
    Themes::register(Theme::make('Not Valid'));
})->throws(InvalidArgumentException::class, 'lowercase');

it('registers a theme written as an anonymous class', function (): void {
    Themes::register(new class extends Theme
    {
        public function getId(): string
        {
            return 'sunset';
        }

        public function getLabel(): string
        {
            return 'Sunset';
        }

        public function getColors(): array
        {
            return ['primary' => Color::Orange];
        }
    });

    expect(Themes::find('sunset')->getLabel())->toBe('Sunset')
        ->and(Themes::find('sunset')->getSwatches())->toBe([Color::Orange[500]]);
});
