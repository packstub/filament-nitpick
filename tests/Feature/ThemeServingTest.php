<?php

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Exceptions;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Tests\Fixtures\RailThemePlugin;
use Packstub\Nitpick\Theme;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    RailThemePlugin::$booted = false;

    Themes::register(
        Theme::make('rail')
            ->colors(['primary' => Color::Lime])
            ->serving(fn (Panel $panel) => $panel
                ->topNavigation()
                ->renderHook(PanelsRenderHook::BODY_END, fn (): string => '<div id="rail-hook"></div>')
                ->colors(['danger' => Color::Pink])
                ->plugin(RailThemePlugin::make())),
    );

    $this->user = createUser();
    $this->actingAs($this->user);
});

it('configures the panel while the theme is active', function (): void {
    pluginFor('admin')->setUserTheme('rail');

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="rail">', false)
        ->assertSee('id="rail-hook"', false)
        ->assertSee('id="rail-theme-marker"', false)
        ->assertSee('--primary-500:'.Color::Lime[500], false)
        ->assertSee('--danger-500:'.Color::Pink[500], false);

    expect(Filament::getPanel('admin')->hasTopNavigation())->toBeTrue()
        ->and(RailThemePlugin::$booted)->toBeTrue();
});

it('leaves the panel alone while another theme is active', function (): void {
    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="aurora">', false)
        ->assertDontSee('id="rail-hook"', false);

    expect(Filament::getPanel('admin')->hasTopNavigation())->toBeFalse()
        ->and(RailThemePlugin::$booted)->toBeFalse();
});

it('keeps the plugin colour overrides above the colours set while serving', function (): void {
    config()->set('nitpick.colors', ['danger' => Color::Red]);
    pluginFor('admin')->setUserTheme('rail');

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('--danger-500:'.Color::Red[500], false);
});

it('marks the themes that configure the panel in the preview manifest', function (): void {
    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('"rail":{"hrefs":[],"label":"Rail","mode":null,"reload":true}', false)
        ->assertSee('"reload":false', false);
});

it('reloads the page when a pick enters or leaves such a theme', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    livewire(Appearance::class)
        ->set('data.theme', 'rail')
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.location.reload()'));

    livewire(Appearance::class)
        ->set('data.theme', 'midnight')
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.location.reload()'));

    livewire(Appearance::class)
        ->set('data.theme', 'ocean')
        ->tap(fn ($component) => expect(json_encode($component->effects))->not->toContain('window.location.reload()'));
});

it('leaves out pages a theme adds while serving, and reports it, so the panel still renders', function (): void {
    Exceptions::fake();

    Themes::register(Theme::make('paged')->serving(fn (Panel $panel) => $panel->pages([Appearance::class, PagedSettings::class])));
    pluginFor('admin')->setUserTheme('paged');

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="paged">', false);

    expect(Filament::getPanel('admin')->getPages())->not->toContain(PagedSettings::class);

    Exceptions::assertReported(fn (LogicException $e): bool => str_contains($e->getMessage(), 'The theme [paged] added pages'));
});

class PagedSettings extends Page
{
    protected static ?string $slug = 'paged-settings';
}
