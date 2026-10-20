<?php

use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Exceptions;
use Packstub\Nitpick\Contracts\Theme as ThemeContract;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\GlobalThemePlugin;
use Packstub\Nitpick\Tests\Fixtures\SlateThemePlugin;
use Packstub\Nitpick\Tests\Fixtures\TideThemePlugin;
use Packstub\Nitpick\Theme;
use Packstub\Nitpick\ThemeManager;

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
    NitpickPlugin::make()->default('nord')->register($panel);

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

it('takes several stylesheets, registered as one asset each', function (): void {
    $theme = Theme::make('duo')->stylesheets([
        __DIR__.'/../../resources/css/themes/midnight.css',
        __DIR__.'/../../resources/css/themes/aurora.css',
    ]);

    Themes::register($theme);

    expect($theme->getStylesheets())->toHaveCount(2)
        ->and($theme->getStylesheetPath())->toEndWith('midnight.css')
        ->and(FilamentAsset::getStyleHref('packstub-theme-duo', package: ThemeManager::ASSET_PACKAGE))->toContain('packstub-theme-duo.css')
        ->and(FilamentAsset::getStyleHref('packstub-theme-duo--2', package: ThemeManager::ASSET_PACKAGE))->toContain('packstub-theme-duo--2.css');
});

it('takes its palette and stylesheets from a Filament theme plugin', function (): void {
    SlateThemePlugin::$booted = false;

    $theme = Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make());

    expect($theme->getColors())->toBe(['primary' => Color::Slate, 'gray' => Color::Zinc])
        ->and($theme->getStylesheets())->toBe([SlateThemePlugin::path('slate.css'), 'https://fonts.example.com/slate.css', SlateThemePlugin::path('slate-corners.css')])
        ->and($theme->getSwatches())->toBe([Color::Slate[500]])
        ->and(SlateThemePlugin::$booted)->toBeFalse();
});

it('accepts a plugin instance and layers its own colours and stylesheets on top', function (): void {
    $theme = Theme::make('slate')
        ->plugin(SlateThemePlugin::make())
        ->colors(['primary' => Color::Rose])
        ->stylesheet('/themes/tweaks.css');

    expect($theme->getColors())->toBe(['primary' => Color::Rose, 'gray' => Color::Zinc])
        ->and($theme->getStylesheets())->toBe([SlateThemePlugin::path('slate.css'), 'https://fonts.example.com/slate.css', SlateThemePlugin::path('slate-corners.css'), '/themes/tweaks.css']);
});

it('keeps a wrapped plugin off the panels: its assets load only as the theme', function (): void {
    Themes::register(Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()));

    $ids = array_map(fn ($asset): string => $asset->getId(), FilamentAsset::getStyles());

    expect($ids)->not->toContain('slate-theme', 'slate-corners')
        ->and(FilamentAsset::getStyleHref('packstub-theme-slate--3', package: ThemeManager::ASSET_PACKAGE))->toContain('packstub-theme-slate--3.css');
});

it('rejects a plugin that is not a Filament plugin', function (): void {
    Theme::make('broken')->plugin(fn (): stdClass => new stdClass)->getColors();
})->throws(InvalidArgumentException::class, 'expects a Filament plugin');

it('reads a Vite theme and an app-wide palette from a plugin, and leaves Tailwind source out', function (): void {
    $manager = app(ColorManager::class);

    $theme = Theme::make('tide')->plugin(fn (): TideThemePlugin => TideThemePlugin::make());

    expect($theme->getColors())->toBe(['gray' => Color::Stone, 'primary' => Color::Cyan])
        ->and($theme->getStylesheets())->toBe(['vite:'.TideThemePlugin::ENTRY])
        ->and(app(ColorManager::class))->toBe($manager)
        ->and(FilamentColor::getColors()['gray'])->toBe(Color::Zinc);
});

it('publishes files only: a URL or a Vite entry is linked where it is', function (): void {
    Themes::register(Theme::make('mixed')->stylesheets([
        'vite:resources/css/themes/mixed.css',
        'https://cdn.example.com/mixed.css',
        __DIR__.'/../../resources/css/themes/midnight.css',
    ]));

    $ids = array_map(fn ($asset): string => $asset->getId(), FilamentAsset::getStyles([ThemeManager::ASSET_PACKAGE]));

    expect($ids)->toContain('packstub-theme-mixed--3')->not->toContain('packstub-theme-mixed', 'packstub-theme-mixed--2');
});

it('configures the panel through serving()', function (): void {
    $panel = Panel::make()->id('serving-test');
    $theme = Theme::make('rail')->serving(fn (Panel $panel, ThemeContract $theme) => $panel->brandName($theme->getLabel()));

    $theme->servingPanel($panel);

    expect($theme->configuresPanel())->toBeTrue()
        ->and(app(ThemeManager::class)->configuresPanel($theme))->toBeTrue()
        ->and(app(ThemeManager::class)->configuresPanel(Theme::make('plain')))->toBeFalse()
        ->and($panel->getBrandName())->toBe('Rail');
});

it('reads the colours a serving() callback sets, for the picker, without touching the app palette', function (): void {
    $theme = Theme::make('tide')->serving(fn (Panel $panel) => $panel->plugin(TideThemePlugin::make()));

    expect($theme->getColors())->toBe(['gray' => Color::Stone, 'primary' => Color::Cyan])
        ->and($theme->getSwatches())->toBe([Color::Cyan[500]])
        ->and($theme->getStylesheets())->toBe([])
        ->and(FilamentColor::getColor('gray'))->not->toBe(Color::Stone);
});

it('keeps its own colours when a serving() callback cannot run outside a request', function (): void {
    Exceptions::fake();

    $theme = Theme::make('needs-user')
        ->colors(['primary' => Color::Lime])
        ->serving(fn (Panel $panel) => $panel->brandName(auth()->user()->name));

    expect($theme->getColors())->toBe(['primary' => Color::Lime]);

    Exceptions::assertReported(ErrorException::class);
});

it('keeps what a plugin registers for the whole app to its theme', function (): void {
    $theme = Theme::make('global')->plugin(fn () => GlobalThemePlugin::make());
    $serving = Theme::make('global-serving')->serving(fn (Panel $panel) => $panel->plugin(GlobalThemePlugin::make()));

    expect($theme->getStylesheets())->toBe([realpath(__DIR__.'/../Fixtures/css/slate.css')]);
    $serving->getColors();

    $styles = collect(FilamentAsset::getStyles(['acme/global-theme']));
    $scripts = collect(FilamentAsset::getScripts(['acme/global-theme']));

    // Still published by filament:assets, never loaded on every page.
    expect($styles)->not->toBeEmpty()
        ->and($styles->every(fn ($asset): bool => $asset->isLoadedOnRequest()))->toBeTrue()
        ->and($scripts)->not->toBeEmpty()
        ->and($scripts->every(fn ($asset): bool => $asset->isLoadedOnRequest()))->toBeTrue()
        ->and(FilamentView::renderHook(PanelsRenderHook::HEAD_END)->toHtml())->not->toContain('global-theme-font');
});
