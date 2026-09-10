<?php

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Packstub\Themes\Contracts\Theme as ThemeContract;
use Packstub\Themes\Facades\Themes;
use Packstub\Themes\Tests\Fixtures\Ocean;
use Packstub\Themes\Theme;
use Packstub\Themes\ThemeManager;
use Packstub\Themes\Themes\Aurora;
use Packstub\Themes\Themes\Midnight;

it('registers the bundled themes', function (): void {
    expect(array_keys(Themes::all()))->toContain('aurora', 'midnight');
    expect(Themes::find('aurora'))->toBeInstanceOf(Aurora::class);
});

it('finds a theme by id, class name or instance', function (): void {
    expect(Themes::find(Midnight::class))->toBeInstanceOf(Midnight::class)
        ->and(Themes::find('midnight'))->toBeInstanceOf(Midnight::class)
        ->and(Themes::find(new Midnight)->getId())->toBe('midnight')
        ->and(Themes::has('ocean'))->toBeTrue()
        ->and(Themes::find('nope'))->toBeNull();
});

it('throws when resolving an unknown theme', function (): void {
    Themes::resolve('nope');
})->throws(InvalidArgumentException::class, 'not registered');

it('rejects a theme with an invalid id', function (): void {
    Themes::register(new class extends Theme
    {
        public function getId(): string
        {
            return 'Not Valid';
        }

        public function getLabel(): string
        {
            return 'Bad';
        }

        public function getColors(): array
        {
            return [];
        }
    });
})->throws(InvalidArgumentException::class, 'lowercase');

it('rejects a class that is not a theme', function (): void {
    Themes::register(stdClass::class);
})->throws(InvalidArgumentException::class, ThemeContract::class);

it('registers a stylesheet as a Filament asset loaded on request', function (): void {
    $href = FilamentAsset::getStyleHref('packstub-theme-aurora', package: ThemeManager::ASSET_PACKAGE);

    expect($href)->toContain('css/packstub/filament-themes/packstub-theme-aurora.css');
});

it('does not register an asset for a palette-only theme', function (): void {
    $ids = array_map(
        fn ($asset): string => $asset->getId(),
        FilamentAsset::getStyles([ThemeManager::ASSET_PACKAGE]),
    );

    expect($ids)->toContain('packstub-theme-aurora')->not->toContain('packstub-theme-ocean');
});

it('resolves a palette to the css variables Filament renders', function (): void {
    $variables = Themes::cssVariables([
        'primary' => Color::Sky,
        'brand' => '#0ea5e9',
        'alias' => [50 => 0, 500 => '#0ea5e9', 600 => 500],
    ]);

    expect($variables['primary-500'])->toBe(Color::Sky[500])
        ->and($variables['brand-500'])->toStartWith('oklch(')
        ->and($variables['alias-50'])->toBe('oklch(1 0 0)')
        ->and($variables['alias-600'])->toBe($variables['alias-500']);
});

it('derives swatches from the palette when a theme declares none', function (): void {
    $swatches = (new Ocean)->getSwatches();

    expect($swatches)->toHaveCount(1)
        ->and($swatches[0])->toBe(Color::Teal[500]);
});
