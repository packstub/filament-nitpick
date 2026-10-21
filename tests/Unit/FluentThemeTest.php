<?php

use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Exceptions;
use Packstub\Nitpick\Contracts\Theme as ThemeContract;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\FluentTheme;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\GlobalThemePlugin;
use Packstub\Nitpick\Tests\Fixtures\SlateThemePlugin;
use Packstub\Nitpick\Tests\Fixtures\TideThemePlugin;
use Packstub\Nitpick\Theme;
use Packstub\Nitpick\ThemeManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

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

it('adds its plugin to the panel while active, and reads only its swatches from it before', function (): void {
    SlateThemePlugin::$booted = false;

    $theme = Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make());

    expect($theme->getColors())->toBe([])
        ->and($theme->getStylesheets())->toBe([])
        ->and($theme->getSwatches())->toBe([Color::Slate[500]])
        ->and($theme->configuresPanel())->toBeTrue()
        ->and(SlateThemePlugin::$booted)->toBeFalse();

    $panel = Panel::make()->id('plugin-test');
    $theme->servingPanel($panel);

    expect($panel->getPlugin('slate-theme'))->toBeInstanceOf(SlateThemePlugin::class)
        ->and($panel->getColors())->toBe(['primary' => Color::Slate, 'gray' => Color::Zinc]);
});

it('accepts a plugin instance and layers its own colours and stylesheets on top', function (): void {
    $theme = Theme::make('slate')
        ->plugin(SlateThemePlugin::make())
        ->colors(['primary' => Color::Rose])
        ->stylesheet('/themes/tweaks.css');

    expect($theme->getColors())->toBe(['primary' => Color::Rose])
        ->and($theme->getStylesheets())->toBe(['/themes/tweaks.css'])
        ->and($theme->getSwatches())->toBe([Color::Rose[500]]);
});

it('keeps a wrapped plugin off the panels until its theme is active', function (): void {
    Themes::register(Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()));

    $ids = array_map(fn ($asset): string => $asset->getId(), FilamentAsset::getStyles());

    expect($ids)->not->toContain('slate-theme', 'slate-corners', 'packstub-theme-slate');
});

it('rejects a plugin that is not a Filament plugin', function (): void {
    Theme::make('broken')->plugin(fn (): stdClass => new stdClass)->servingPanel(Panel::make()->id('broken'));
})->throws(InvalidArgumentException::class, 'expects a Filament plugin');

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

it('reads the swatches from the colours a plugin sets, without touching the app palette', function (FluentTheme $theme): void {
    expect($theme->getSwatches())->toBe([Color::Cyan[500]])
        ->and($theme->getColors())->toBe([])
        ->and(FilamentColor::getColor('gray'))->not->toBe(Color::Stone);

    // Nor on a colour manager built after it.
    app()->forgetScopedInstances();
    FilamentColor::clearResolvedInstances();

    expect(FilamentColor::getColor('gray'))->not->toBe(Color::Stone);
})->with([
    'plugin()' => fn (): FluentTheme => Theme::make('tide')->plugin(fn (): TideThemePlugin => TideThemePlugin::make()),
    'serving()' => fn (): FluentTheme => Theme::make('tide')->serving(fn (Panel $panel) => $panel->plugin(TideThemePlugin::make())),
]);

it('keeps its own colours when a serving() callback cannot run outside a request', function (): void {
    Exceptions::fake();

    $theme = Theme::make('needs-user')
        ->colors(['primary' => Color::Lime])
        ->serving(fn (Panel $panel) => $panel->brandName(auth()->user()->name));

    expect($theme->getSwatches())->toBe([Color::Lime[500]]);

    Exceptions::assertReported(ErrorException::class);
});

it('publishes what a theme plugin brings when Filament publishes assets', function (string $command): void {
    Themes::register(Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()));

    expect(FilamentAsset::getStyles(['vendor/slate-theme']))->toBe([]);

    startCommand($command);

    $styles = collect(FilamentAsset::getStyles(['vendor/slate-theme']));

    expect($styles->map->getId()->all())->toBe(['slate-theme', 'slate-fonts', 'slate-corners'])
        ->and($styles->every(fn ($asset): bool => $asset->isLoadedOnRequest()))->toBeTrue()
        ->and(collect(FilamentAsset::getScripts(['vendor/slate-theme']))->map->getId()->all())->toBe(['slate-script']);
})->with(['filament:assets', 'filament:upgrade', 'filament:install']);

it('publishes nothing for any other command', function (): void {
    Themes::register(Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()));

    startCommand('migrate');

    expect(FilamentAsset::getStyles(['vendor/slate-theme']))->toBe([]);
});

it('keeps what a plugin registers for the whole app off the panels, and still publishes it', function (): void {
    $theme = Theme::make('global')->plugin(fn () => GlobalThemePlugin::make());
    Themes::register($theme, Theme::make('global-serving')->serving(fn (Panel $panel) => $panel->plugin(GlobalThemePlugin::make())));

    // Reading the swatches registers nothing.
    $theme->getSwatches();

    expect(FilamentAsset::getStyles(['acme/global-theme']))->toBe([]);

    startCommand('filament:assets');

    $styles = collect(FilamentAsset::getStyles(['acme/global-theme']));
    $scripts = collect(FilamentAsset::getScripts(['acme/global-theme']));

    // Published by filament:assets, never loaded on every page.
    expect($styles)->not->toBeEmpty()
        ->and($styles->every(fn ($asset): bool => $asset->isLoadedOnRequest()))->toBeTrue()
        ->and($scripts)->not->toBeEmpty()
        ->and($scripts->every(fn ($asset): bool => $asset->isLoadedOnRequest()))->toBeTrue()
        ->and(FilamentView::renderHook(PanelsRenderHook::HEAD_END)->toHtml())->not->toContain('global-theme-font');

    // Nor on the managers built after it, the way a queue worker builds new ones between jobs.
    app()->forgetScopedInstances();
    FilamentView::clearResolvedInstances();

    expect(FilamentView::renderHook(PanelsRenderHook::HEAD_END)->toHtml())->not->toContain('global-theme-font')
        ->and(collect(FilamentAsset::getStyles(['acme/global-theme']))->every(fn ($asset): bool => $asset->isLoadedOnRequest()))->toBeTrue();
});

it('reports a theme that cannot configure a panel while assets are published, and publishes the rest', function (): void {
    Exceptions::fake();

    Themes::register(
        Theme::make('needs-user')->serving(fn (Panel $panel) => $panel->brandName(auth()->user()->name)),
        Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()),
    );

    startCommand('filament:assets');

    Exceptions::assertReported(ErrorException::class);

    expect(FilamentAsset::getStyles(['vendor/slate-theme']))->not->toBeEmpty();
});

it('leaves a plugin its own asset objects as they were', function (): void {
    $stylesheet = Css::make('kept-theme', __DIR__.'/../Fixtures/css/slate.css');

    $plugin = new class($stylesheet) implements Plugin
    {
        public function __construct(public Css $stylesheet) {}

        public function getId(): string
        {
            return 'kept-theme';
        }

        public function register(Panel $panel): void
        {
            FilamentAsset::register([$this->stylesheet], 'acme/kept-theme');
        }

        public function boot(Panel $panel): void {}
    };

    Themes::register(Theme::make('kept')->plugin($plugin));
    startCommand('filament:assets');

    expect($stylesheet->isLoadedOnRequest())->toBeFalse()
        ->and(FilamentAsset::getStyles(['acme/kept-theme'])[0]->isLoadedOnRequest())->toBeTrue();
});

function startCommand(string $command): void
{
    event(new CommandStarting($command, new ArrayInput([]), new NullOutput));
}
