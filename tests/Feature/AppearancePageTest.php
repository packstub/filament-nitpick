<?php

use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Packstub\Nitpick\Facades\Themes;
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
        ->assertSet('data.theme', 'aurora')
        ->set('data.theme', 'midnight')
        ->assertHasNoErrors();

    expect(ThemePreference::query()->where('user_id', $this->user->id)->value('theme_id'))->toBe('midnight');
});

it('asks a pick for a reload only when the picker saves it', function (): void {
    $this->get(Appearance::getUrl())
        ->assertSee('window.PackstubThemes.pick(\'midnight\', { reload: true })', false);

    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->persist(false));

    $this->get(Appearance::getUrl())
        ->assertSee('window.PackstubThemes.pick(\'midnight\', { reload: false })', false);
});

it('is closed when user selection is turned off', function (): void {
    config()->set('nitpick-test.selectable', false);

    $this->get(Appearance::getUrl())->assertForbidden();
});

it('stamps the panel with the active theme', function (): void {
    pluginFor('admin')->setUserTheme('midnight');

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="midnight">', false)
        ->assertSee('packstub-theme-midnight.css', false)
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
        ->assertDontSee('pst-theme-option-description', false);
});

it('shows descriptions when the panel asks for them', function (): void {
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->descriptions());

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('<span class="pst-theme-option-description">'.e(__('nitpick::themes.themes.midnight.description')).'</span>', false)
        ->assertDontSee('title="'.e(__('nitpick::themes.themes.midnight.description')).'"', false);
});

it('shows empty swatches for a theme whose colours are unknown', function (): void {
    Themes::register(Theme::make('blank'));

    $html = $this->get(Appearance::getUrl())->assertOk()->getContent();

    expect(substr_count($html, 'pst-theme-swatch pst-theme-swatch-empty'))->toBe(3);
});

it('can leave the swatches out', function (): void {
    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->swatches(false));

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('Midnight')
        ->assertDontSee('pst-theme-swatches', false);
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
        ->assertDontSee('fi-grid-cols pst-theme-picker', false);

    pluginFor('admin')->picker(fn (ThemePicker $picker) => $picker->columns(3));

    $this->get(Appearance::getUrl())
        ->assertOk()
        ->assertSee('--cols-lg: repeat(3, minmax(0, 1fr))', false);
});
