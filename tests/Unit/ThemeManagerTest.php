<?php

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Packstub\Nitpick\Contracts\Theme as ThemeContract;
use Packstub\Nitpick\Facades\Nitpick;
use Packstub\Nitpick\Tests\Fixtures\Ocean;
use Packstub\Nitpick\Theme;
use Packstub\Nitpick\ThemeManager;
use Packstub\Nitpick\Themes\Aurora;
use Packstub\Nitpick\Themes\Midnight;

it('registers the bundled themes', function (): void {
    expect(array_keys(Nitpick::all()))->toContain('aurora', 'midnight');
    expect(Nitpick::find('aurora'))->toBeInstanceOf(Aurora::class);
});

it('finds a theme by id, class name or instance', function (): void {
    expect(Nitpick::find(Midnight::class))->toBeInstanceOf(Midnight::class)
        ->and(Nitpick::find('midnight'))->toBeInstanceOf(Midnight::class)
        ->and(Nitpick::find(new Midnight)->getId())->toBe('midnight')
        ->and(Nitpick::has('ocean'))->toBeTrue()
        ->and(Nitpick::find('nope'))->toBeNull();
});

it('throws when resolving an unknown theme', function (): void {
    Nitpick::resolve('nope');
})->throws(InvalidArgumentException::class, 'not registered');

it('rejects a theme with an invalid id', function (): void {
    Nitpick::register(new class extends Theme
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
    Nitpick::register(stdClass::class);
})->throws(InvalidArgumentException::class, ThemeContract::class);

it('registers a stylesheet as a Filament asset loaded on request', function (): void {
    $href = FilamentAsset::getStyleHref('nitpick-theme-aurora', package: ThemeManager::ASSET_PACKAGE);

    expect($href)->toContain('css/packstub/filament-nitpick/nitpick-theme-aurora.css');
});

it('does not register an asset for a palette-only theme', function (): void {
    $ids = array_map(
        fn ($asset): string => $asset->getId(),
        FilamentAsset::getStyles([ThemeManager::ASSET_PACKAGE]),
    );

    expect($ids)->toContain('nitpick-theme-aurora')->not->toContain('nitpick-theme-ocean');
});

it('resolves a palette to the css variables Filament renders', function (): void {
    $variables = Nitpick::cssVariables([
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
