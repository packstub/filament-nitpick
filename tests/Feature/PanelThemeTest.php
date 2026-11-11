<?php

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Packstub\Nitpick\Contracts\PanelThemeStore;
use Packstub\Nitpick\Events\PanelThemeChanged;
use Packstub\Nitpick\Facades\Nitpick;
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\PanelTheme;
use Packstub\Nitpick\Theme;
use Packstub\Nitpick\ThemeManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->manager = createUser(['email' => 'manager@example.com']);
    $this->member = createUser();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    pluginFor('admin')->picker(manage: fn (): bool => auth()->user()?->email === 'manager@example.com');
});

function setForEveryone(Closure $change): void
{
    $current = auth()->user();
    test()->actingAs(test()->manager);
    $change(pluginFor('admin'));
    $current === null ? auth()->logout() : test()->actingAs($current);
}

it('never reads the store, nor shows the section, unless the panel lets someone manage', function (): void {
    pluginFor('admin')->picker(manage: null);
    (fn () => $this->manage = null)->call(pluginFor('admin'));
    app()->bind(PanelThemeStore::class, fn () => throw new RuntimeException('read'));
    $this->actingAs($this->manager);

    expect(pluginFor('admin')->getPanelTheme())->toBeNull()
        ->and(pluginFor('admin')->getActiveTheme()->getId())->toBe('aurora');

    $this->get(Appearance::getUrl())->assertOk()->assertDontSee('For everyone');
});

it('shows managers a For everyone section, and nobody else', function (): void {
    $this->actingAs($this->manager)->get(Appearance::getUrl())->assertSee('For everyone')->assertSee('Lock for everyone');
    $this->actingAs($this->member)->get(Appearance::getUrl())->assertDontSee('For everyone');
});

it('serves the theme a manager set to everyone who has not picked one', function (): void {
    Event::fake([PanelThemeChanged::class]);
    setForEveryone(fn ($plugin) => $plugin->setPanelTheme('midnight'));
    $this->actingAs($this->member);

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('midnight')
        ->and(pluginFor('admin')->getCodeDefaultTheme()->getId())->toBe('aurora');

    $this->get(Appearance::getUrl())->assertSee('Follows Midnight');

    Event::assertDispatched(PanelThemeChanged::class, fn (PanelThemeChanged $event): bool => $event->theme->themeId === 'midnight' && $event->tenant === null);
});

it('lets a user\'s own pick win over it', function (): void {
    setForEveryone(fn ($plugin) => $plugin->setPanelTheme('midnight'));
    $this->actingAs($this->member);
    pluginFor('admin')->setUserTheme('ocean');

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('ocean');
});

it('refuses anyone who may not manage', function (): void {
    $this->actingAs($this->member);

    pluginFor('admin')->setPanelTheme('midnight');
})->throws(AuthorizationException::class);

it('refuses a theme the panel does not offer', function (): void {
    pluginFor('admin')->themes(['aurora', 'midnight']);
    $this->actingAs($this->manager);

    pluginFor('admin')->setPanelTheme('ocean');
})->throws(InvalidArgumentException::class, 'not offered');

it('locks everyone to it: their own picks set aside, the picker closed to all but managers', function (): void {
    $this->actingAs($this->member);
    pluginFor('admin')->picker(accent: true)->setUserTheme('ocean');
    pluginFor('admin')->setUserAccent('teal');

    setForEveryone(function ($plugin): void {
        $plugin->setPanelTheme('midnight');
        $plugin->setPanelAccent('rose');
        $plugin->setPanelLocked(true);
    });

    expect(pluginFor('admin'))
        ->hasPicker()->toBeFalse()
        ->getUserTheme()->toBeNull()
        ->getActiveTheme()->getId()->toBe('midnight')
        ->getAccent()->toBe('rose');

    $this->get(Appearance::getUrl())->assertForbidden();

    // Managers keep the page, without a picker of their own, to change or unlock it.
    $this->flushSession();
    $this->actingAs($this->manager);

    expect(pluginFor('admin')->hasPicker())->toBeTrue()
        ->and(pluginFor('admin')->hasUserChoice())->toBeFalse();

    $this->get(Appearance::getUrl())->assertOk()->assertSee('Locked: everyone sees this theme', false)->assertDontSee('name="data.theme"', false);
});

it('applies a manager\'s accent to everyone, under a user\'s own', function (): void {
    pluginFor('admin')->picker(accent: true);
    setForEveryone(fn ($plugin) => $plugin->setPanelAccent('indigo'));
    $this->actingAs($this->member);

    expect(pluginFor('admin')->getAccent())->toBe('indigo')
        ->and(pluginFor('admin')->getColorsFor(app(ThemeManager::class)->resolve('aurora'))['primary'])->toBe(Color::Indigo);

    $this->get(Appearance::getUrl())->assertSee('Panel&#039;s colour', false);

    pluginFor('admin')->setUserAccent('rose');

    expect(pluginFor('admin')->getAccent())->toBe('rose');
});

it('refuses an accent for everyone that cannot be used', function (): void {
    pluginFor('admin')->picker(accent: true);
    $this->actingAs($this->manager);

    pluginFor('admin')->setPanelAccent('yellow');
})->throws(InvalidArgumentException::class);

it('keeps a setting per tenant, over the panel\'s', function (): void {
    $acme = createUser(['name' => 'Acme']);
    $globex = createUser(['name' => 'Globex']);

    setForEveryone(fn ($plugin) => $plugin->setPanelTheme('ocean'));
    Filament::setTenant($acme, isQuiet: true);
    setForEveryone(fn ($plugin) => $plugin->setPanelTheme('midnight'));
    $this->actingAs($this->member);

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('midnight');

    Filament::setTenant($globex, isQuiet: true);
    app()->forgetScopedInstances();

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('ocean');

    $this->actingAs($this->manager);
    Filament::setTenant($acme, isQuiet: true);

    $this->get(Appearance::getUrl())->assertSee('For Acme');
});

it('keeps a tenant\'s key as text, so any key type fits', function (): void {
    $tenant = new class extends Model
    {
        protected $keyType = 'string';

        public $incrementing = false;
    };
    $tenant->forceFill(['id' => '01JC8Z6M4V9Q2X7Y5T3R1P0N8K']);

    app(PanelThemeStore::class)->set('admin', $tenant, new PanelTheme('midnight'));

    expect(app(PanelThemeStore::class)->get('admin', $tenant)?->themeId)->toBe('midnight')
        ->and(app(PanelThemeStore::class)->get('admin', null))->toBeNull();
});

it('sets it from the For everyone section, and shows the manager the result when they follow the default', function (): void {
    $this->actingAs($this->manager);

    livewire(Appearance::class)
        ->assertSet('data.panel_theme', ThemePicker::PANEL_DEFAULT)
        ->set('data.panel_theme', 'midnight')
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.Nitpick.pick(\"midnight\")'))
        ->set('data.panel_locked', true)
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.Nitpick.reload()'));

    expect(app(PanelThemeStore::class)->get('admin', null))
        ->themeId->toBe('midnight')
        ->locked->toBeTrue();
});

it('forgets the setting once nothing is set', function (): void {
    setForEveryone(function ($plugin): void {
        $plugin->setPanelTheme('midnight');
        $plugin->setPanelTheme(null);
    });

    expect(DB::table('nitpick_panel_themes')->count())->toBe(0);
});

it('keeps the panel working, as if nothing were set, before the table is migrated', function (): void {
    Schema::drop('nitpick_panel_themes');
    $this->actingAs($this->member);

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('aurora');

    $this->get(Filament::getPanel('admin')->getUrl())->assertOk();
});

it('reloads the manager\'s page when the theme for everyone configures the panel', function (): void {
    Nitpick::register(Theme::make('rail')->panel(fn ($panel) => $panel->topNavigation()));
    $this->actingAs($this->manager);

    livewire(Appearance::class)
        ->set('data.panel_theme', 'rail')
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.Nitpick.reload()'));
});
