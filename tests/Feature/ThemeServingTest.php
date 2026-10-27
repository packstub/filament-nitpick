<?php

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Exceptions;
use Packstub\Nitpick\Contracts\ConfiguresPanel;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Tests\Fixtures\RailThemePlugin;
use Packstub\Nitpick\Tests\Fixtures\SlateThemePlugin;
use Packstub\Nitpick\Theme;
use Packstub\Nitpick\Theme as BaseTheme;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    RailThemePlugin::$booted = false;

    Themes::register(
        Theme::make('rail')
            ->colors(['primary' => Color::Lime])
            ->panel(fn (Panel $panel) => $panel
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
        ->assertSee('--danger-500:'.Color::Pink[500], false)
        ->assertSee('fi-body-has-top-navigation', false);

    expect(RailThemePlugin::$booted)->toBeTrue();
});

it('adds a theme plugin to the panel while its theme is active', function (): void {
    SlateThemePlugin::$booted = false;
    Themes::register(Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()));
    $panel = Filament::getPanel('admin');
    pluginFor('admin')->setUserTheme('slate');

    $this->get($panel->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="slate">', false)
        ->assertSee('vendor/slate-theme/slate-theme.css', false)
        ->assertSee('vendor/slate-theme/slate-corners.css', false)
        ->assertSee('--primary-500:'.Color::Slate[500], false)
        ->assertSee('"slate":{"hrefs":[],"label":"Slate","mode":null,"reload":true}', false);

    expect(SlateThemePlugin::$booted)->toBeTrue()
        ->and($panel->getPlugins())->not->toHaveKey('slate-theme');
});

it('leaves a theme plugin off the panel while another theme is active', function (): void {
    SlateThemePlugin::$booted = false;
    Themes::register(Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()));

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="aurora">', false)
        ->assertDontSee('vendor/slate-theme/', false);

    expect(SlateThemePlugin::$booted)->toBeFalse();
});

it('puts the panel back once the request ends, so the next request starts clean (Octane)', function (): void {
    $panel = Filament::getPanel('admin');
    $plugins = array_keys($panel->getPlugins());
    pluginFor('admin')->setUserTheme('rail');

    $this->get($panel->getUrl())->assertOk()->assertSee('fi-body-has-top-navigation', false);

    expect($panel->hasTopNavigation())->toBeFalse()
        ->and(array_keys($panel->getPlugins()))->toBe($plugins);

    // The next request in the same process, on another theme. (Filament's render hooks and
    // colours live in services Octane builds anew for each request; the panel is kept.)
    pluginFor('admin')->setUserTheme(null);

    $this->get($panel->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="aurora">', false)
        ->assertDontSee('fi-body-has-top-navigation', false);
});

it('previews the colours a theme class sets while serving in its palette', function (): void {
    Themes::register(new class extends BaseTheme implements ConfiguresPanel
    {
        public function getId(): string
        {
            return 'newsroom';
        }

        public function getLabel(): string
        {
            return 'Newsroom';
        }

        public function getColors(): array
        {
            return ['primary' => Color::Red];
        }

        public function configurePanel(Panel $panel): void
        {
            $panel->colors(['danger' => Color::Pink]);
        }
    });
    pluginFor('admin')->setUserTheme('newsroom');

    $html = $this->get(Filament::getPanel('admin')->getUrl())->assertOk()->getContent();

    expect($html)->toMatch('/\[data-packstub-theme="newsroom"\] \{[^}]*--danger-500: '.preg_quote(Color::Pink[500], '/').';/');
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
        ->assertSee('"reload":false', false)
        // The live preview stops at the light/dark mode for such a theme, or when leaving one.
        ->assertSee('return !! (manifest.themes[slug].reload || (active && active.reload));', false)
        ->assertSee('if (needsReload(slug)) {', false);
});

it('reloads the page when a pick enters or leaves such a theme', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    livewire(Appearance::class)
        ->set('data.theme', 'rail')
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.PackstubThemes.reload()'));

    livewire(Appearance::class)
        ->set('data.theme', 'midnight')
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.location.reload()'));

    livewire(Appearance::class)
        ->set('data.theme', 'ocean')
        ->tap(fn ($component) => expect(json_encode($component->effects))->not->toContain('window.location.reload()'));
});

it('leaves out pages a theme adds while serving, and reports it, so the panel still renders', function (): void {
    Exceptions::fake();

    Themes::register(Theme::make('paged')->panel(fn (Panel $panel) => $panel->pages([Appearance::class, PagedSettings::class])));
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

it('serves the default theme and tells the user when their theme fails to configure the panel', function (): void {
    Exceptions::fake();

    Themes::register(
        Theme::make('broken')
            ->label('Broken')
            ->panel(fn (Panel $panel) => $panel
                ->topNavigation()
                ->renderHook(PanelsRenderHook::BODY_END, fn (): string => '<div id="broken-hook"></div>')
                ->plugin(new class extends RailThemePlugin
                {
                    public function getId(): string
                    {
                        return 'broken-theme';
                    }

                    public function boot(Panel $panel): void
                    {
                        throw new RuntimeException('Broken needs a custom theme on the panel.');
                    }
                })),
    );
    $panel = Filament::getPanel('admin');
    pluginFor('admin')->setUserTheme('broken');

    $this->get($panel->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="aurora">', false)
        ->assertDontSee('id="broken-hook"', false)
        ->assertDontSee('fi-body-has-top-navigation', false);

    Notification::assertNotified(
        Notification::make()
            ->warning()
            ->title('Broken is unavailable right now')
            ->body('This panel is shown in Aurora instead. You can pick another theme from Appearance.'),
    );

    expect($panel->hasTopNavigation())->toBeFalse()
        ->and($panel->getPlugins())->not->toHaveKey('broken-theme')
        // The choice is kept: the theme comes back once it works.
        ->and(pluginFor('admin')->getUserTheme($this->user)?->getId())->toBe('broken');

    Exceptions::assertReported(fn (RuntimeException $e): bool => str_contains($e->getMessage(), 'The theme [broken] failed to configure the [admin] panel, so the default theme [aurora] was served instead: Broken needs a custom theme')
        && $e->getPrevious()?->getMessage() === 'Broken needs a custom theme on the panel.');
});

it('falls back to a default theme that configures the panel itself', function (): void {
    Exceptions::fake();

    Themes::register(Theme::make('broken')->panel(fn (Panel $panel) => throw new RuntimeException('Nope')));
    pluginFor('admin')->default('rail')->setUserTheme('broken');

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="rail">', false)
        ->assertSee('id="rail-hook"', false)
        ->assertSee('fi-body-has-top-navigation', false);

    expect(RailThemePlugin::$booted)->toBeTrue();
});

it('throws when the default theme itself fails to configure the panel', function (): void {
    Themes::register(Theme::make('broken')->panel(fn (Panel $panel) => throw new RuntimeException('The panel default is broken.')));
    pluginFor('admin')->default('broken');

    $this->withoutExceptionHandling()
        ->get(Filament::getPanel('admin')->getUrl());
})->throws(RuntimeException::class, 'The panel default is broken.');
