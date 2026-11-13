<?php

use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Filament\Support\Colors\Color;
use Packstub\Nitpick\Facades\Nitpick;
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Models\ThemePreference;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Theme;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = createUser();
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('is linked from the user menu when users may switch', function (): void {
    $items = collect(Filament::getPanel('admin')->getUserMenuItems())->flatten();

    expect($items->contains(fn ($item): bool => $item->getName() === 'nitpick-appearance'))->toBeTrue();
});

it('lists the offered themes', function (): void {
    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('Aurora')
        ->assertSee('Midnight')
        ->assertSee('Ocean');
});

it('saves the theme as soon as it is picked', function (): void {
    livewire(Appearance::class)
        ->assertSet('data.theme', ThemePicker::PANEL_DEFAULT)
        ->set('data.theme', 'midnight')
        ->assertHasNoErrors();

    expect(ThemePreference::query()->where('user_id', $this->user->id)->value('theme_id'))->toBe('midnight');
});

it('asks a pick for a reload only when the picker saves it', function (): void {
    $this->get(Appearance::getUrl())
        ->assertSee('window.Nitpick.pick(\'midnight\', { reload: true })', false);

    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->persist(false));

    $this->get(Appearance::getUrl())
        ->assertSee('window.Nitpick.pick(\'midnight\', { reload: false })', false);
});

it('is closed when user selection is turned off', function (): void {
    config()->set('nitpick-test.selectable', false);

    $this->get(Appearance::getUrl())->assertForbidden();
});

it('stamps the panel with the active theme', function (): void {
    pluginFor('admin')->setUserTheme('midnight');

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="nitpick-theme" content="midnight">', false)
        ->assertSee('nitpick-theme-midnight.css', false)
        ->assertSee('--default-theme-mode: dark', false)
        ->assertSee('nitpick-palettes', false);
});

it('saves a pick quietly: the new look is the confirmation', function (): void {
    livewire(Appearance::class)
        ->set('data.theme', 'midnight')
        ->assertNotNotified();

    expect(ThemePreference::query()->where('user_id', $this->user->id)->value('theme_id'))->toBe('midnight');
});

it('keeps descriptions off the cards by default, as their tooltip', function (): void {
    $description = __('nitpick::themes.themes.midnight.description');

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('title="'.e($description).'"', false)
        ->assertDontSee('nitpick-option-description', false);
});

it('shows descriptions when the panel asks for them', function (): void {
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->descriptions());

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('<span class="nitpick-option-description">'.e(__('nitpick::themes.themes.midnight.description')).'</span>', false)
        ->assertDontSee('title="'.e(__('nitpick::themes.themes.midnight.description')).'"', false);
});

it('leaves the swatch row empty for a theme whose colours are unknown', function (): void {
    Nitpick::register(Theme::make('blank')->label('Blank'));
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->previews(false));

    $html = $this->get(Appearance::getUrl())->assertOk()->getContent();

    expect($html)
        ->toMatch('/<span class="nitpick-swatches" aria-hidden="true">(\s|<!--.*?-->)*<\/span>(\s|<!--.*?-->)*<span class="nitpick-option-label">\s*Blank/')
        ->not->toContain('nitpick-swatch-empty');
});

it('leaves the swatch row out when no theme has known colours', function (): void {
    Nitpick::register(Theme::make('blank'));
    Nitpick::register(Theme::make('plain'));
    pluginFor('admin')->themes(['blank', 'plain']);

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('Plain')
        ->assertDontSee('nitpick-swatches', false);
});

it('can leave the swatches out', function (): void {
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->swatches(false));

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('Midnight')
        ->assertDontSee('nitpick-swatches', false);
});

it('opens the picker in a modal from the user menu, in fixed columns', function (): void {
    $item = collect(Filament::getPanel('admin')->getUserMenuItems())->flatten()
        ->first(fn ($item): bool => $item->getName() === 'nitpick-appearance');

    expect($item->getUrl())->toBeNull();

    livewire(Topbar::class)
        ->mountAction('nitpick-appearance')
        ->assertActionMounted('nitpick-appearance')
        ->assertSchemaComponentExists('theme', checkComponentUsing: fn (ThemePicker $picker): bool => $picker->getColumns() === NitpickPlugin::MODAL_COLUMNS);
});

it('links the Appearance page instead when the modal is turned off', function (): void {
    pluginFor('admin')->picker(modal: false);

    $item = collect(Filament::getPanel('admin')->getUserMenuItems())->flatten()
        ->first(fn ($item): bool => $item->getName() === 'nitpick-appearance');

    expect($item->getUrl())->toBe(Appearance::getUrl());
});

it('lets the cards fill the page unless columns are set', function (): void {
    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertDontSee('fi-grid-cols nitpick-picker', false);

    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->columns(3));

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('--cols-lg: repeat(3, minmax(0, 1fr))', false);
});

it('offers "Panel default" first, picked for anyone who never chose, naming the theme it follows', function (): void {
    $this->get(Appearance::getUrl())
        ->assertSeeInOrder(['Panel default', 'Follows Aurora', 'Aurora', 'Midnight'])
        ->assertSee('value="'.ThemePicker::PANEL_DEFAULT.'"', false)
        // Picking it previews the theme it follows.
        ->assertSee("window.Nitpick.pick('aurora', { reload: true })", false);

    livewire(Appearance::class)->assertSet('data.theme', ThemePicker::PANEL_DEFAULT);
});

it('shows the user\'s own choice instead of "Panel default" once they picked one', function (): void {
    pluginFor('admin')->setUserTheme('midnight');

    livewire(Appearance::class)->assertSet('data.theme', 'midnight');
});

it('clears the user\'s choice when they go back to "Panel default"', function (): void {
    pluginFor('admin')->setUserTheme('midnight');

    livewire(Appearance::class)->set('data.theme', ThemePicker::PANEL_DEFAULT);

    expect(ThemePreference::query()->where('user_id', $this->user->id)->exists())->toBeFalse()
        ->and(pluginFor('admin')->getActiveTheme()->getId())->toBe('aurora');
});

it('follows the panel default when it changes later, for users on "Panel default"', function (): void {
    livewire(Appearance::class)->set('data.theme', ThemePicker::PANEL_DEFAULT);

    pluginFor('admin')->default('midnight');

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('midnight');
});

it('reloads when "Panel default" follows a theme that configures the panel', function (): void {
    Nitpick::register(Theme::make('rail')->panel(fn ($panel) => $panel->topNavigation()));
    pluginFor('admin')->default('rail')->setUserTheme('aurora');

    livewire(Appearance::class)
        ->set('data.theme', ThemePicker::PANEL_DEFAULT)
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.Nitpick.reload()'));
});

it('leaves "Panel default" out with ->defaultOption(false), showing the active theme as picked', function (): void {
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->defaultOption(false));

    $this->get(Appearance::getUrl())->assertDontSee('Panel default');

    livewire(Appearance::class)->assertSet('data.theme', 'aurora');
});

it('shows each theme in light and dark on its card', function (): void {
    $html = $this->get(Appearance::getUrl())->assertOk()->getContent();

    // Panel default, Aurora, Midnight and Ocean: two halves each.
    expect(substr_count($html, 'class="nitpick-preview-mode"'))->toBe(8)
        ->and($html)->not->toContain('class="nitpick-swatches"')
        // Aurora's own look, through HasPreview.
        ->toContain('--nitpick-preview-background: linear-gradient(135deg, #cdeafe 0%, #e3f8ff 50%, #fde2f1 100%);')
        // Ocean's, drawn from its palette: teal buttons on Filament's surfaces.
        ->toContain('--nitpick-preview-primary: '.Color::Teal[600].';');
});

it('draws a preview from the palette, with the colours the panel would add on top', function (): void {
    pluginFor('admin')->colors(['primary' => Color::Rose]);

    $html = $this->get(Appearance::getUrl())->getContent();

    expect($html)->toContain('--nitpick-preview-primary: '.Color::Rose[600].';');
});

it('takes a preview from Theme::make()->preview(), over the palette', function (): void {
    Nitpick::register(Theme::make('ink')->colors(['primary' => Color::Indigo])->preview(['dark' => ['background' => '#010203']]));

    $html = $this->get(Appearance::getUrl())->getContent();

    expect($html)->toContain('--nitpick-preview-background: #010203;')
        ->toContain('--nitpick-preview-primary: '.Color::Indigo[600].';');
});

it('keeps the swatches of a theme whose colours are unknown, in a row the previews\' height', function (): void {
    Nitpick::register(Theme::make('blank')->swatches(['#123456']));

    $html = $this->get(Appearance::getUrl())->getContent();

    expect($html)->toMatch('/<span class="nitpick-preview nitpick-preview-empty" aria-hidden="true">(\s|<!--.*?-->)*<span class="nitpick-swatch" style="background: #123456"><\/span>/');
});

it('shows swatches instead with ->previews(false)', function (): void {
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->previews(false));

    $html = $this->get(Appearance::getUrl())->getContent();

    expect($html)->not->toContain('nitpick-preview-mode')
        ->toContain('class="nitpick-swatches"');
});

it('skips preview values that are not text instead of failing the picker', function (): void {
    Nitpick::register(Theme::make('odd')->colors(['primary' => Color::Indigo])->preview(['light' => ['background' => null, 'surface' => ['#fff']], 'dark' => '#000']));

    $html = $this->get(Appearance::getUrl())->assertOk()->getContent();

    expect($html)->toContain('--nitpick-preview-primary: '.Color::Indigo[600].';');
});

it('keeps a swatch to one declaration', function (): void {
    Nitpick::register(Theme::make('loud')->swatches(['red; position: fixed; inset: 0']));

    $html = $this->get(Appearance::getUrl())->getContent();

    expect($html)->toContain('style="background: red position: fixed inset: 0"')
        ->not->toContain('red; position');
});
