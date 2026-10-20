<?php

use Filament\Facades\Filament;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Models\ThemePreference;

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
