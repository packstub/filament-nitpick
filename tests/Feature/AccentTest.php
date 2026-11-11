<?php

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Event;
use Packstub\Nitpick\Contracts\ThemePreferenceStore;
use Packstub\Nitpick\Events\AccentChanged;
use Packstub\Nitpick\Filament\Forms\Components\AccentPicker;
use Packstub\Nitpick\Filament\Pages\Appearance;
use Packstub\Nitpick\Models\ThemePreference;
use Packstub\Nitpick\Support\Accent;
use Packstub\Nitpick\ThemeManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = createUser();
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function withAccents(): void
{
    pluginFor('admin')->picker(accent: true);
}

it('is off until the panel turns it on', function (): void {
    expect(pluginFor('admin')->hasAccent())->toBeFalse();

    $this->get(Appearance::getUrl())->assertDontSee('Accent colour')->assertDontSee('id="nitpick-accent"', false);

    pluginFor('admin')->setUserAccent('indigo');
})->throws(LogicException::class, '->picker(accent: true)');

it('offers Filament\'s colours, the theme\'s own and a colour of the user\'s, under the themes', function (): void {
    withAccents();

    $this->get(Appearance::getUrl())
        ->assertSeeInOrder(['Midnight', 'Accent colour', 'Theme&#039;s colour', 'Indigo', 'Your own colour'], false)
        ->assertSee('type="color"', false);

    livewire(Appearance::class)->assertSet('data.accent', AccentPicker::THEME);
});

it('saves an accent and paints the panel\'s primary with it, whatever the theme', function (): void {
    withAccents();
    Event::fake([AccentChanged::class]);

    livewire(Appearance::class)
        ->set('data.accent', 'indigo')
        ->assertNotNotified()
        ->tap(fn ($component) => expect(json_encode($component->effects))->toContain('window.Nitpick.accent('));

    $plugin = pluginFor('admin');

    expect(ThemePreference::query()->where('user_id', $this->user->id)->first())
        ->theme_id->toBeNull()
        ->accent->toBe('indigo')
        ->and($plugin->getColorsFor(app(ThemeManager::class)->resolve('aurora'))['primary'])->toBe(Color::Indigo)
        ->and($plugin->getColorsFor(app(ThemeManager::class)->resolve('midnight'))['primary'])->toBe(Color::Indigo);

    Event::assertDispatched(AccentChanged::class, fn (AccentChanged $event): bool => $event->accent === 'indigo' && $event->panelId === 'admin');
});

it('puts the accent in the head, over the theme\'s stylesheet', function (): void {
    withAccents();
    pluginFor('admin')->setUserAccent('#4F46E5');

    $html = $this->get(Filament::getPanel('admin')->getUrl())->assertOk()->getContent();

    expect($html)->toMatch('/<style id="nitpick-accent">:root\[data-nitpick-theme\] \{ --primary-50: oklch\([^)]+\);/')
        ->and(strpos($html, 'id="nitpick-accent"'))->toBeGreaterThan(strpos($html, 'id="nitpick-theme-active"'))
        ->and(pluginFor('admin')->getUserAccent())->toBe('#4f46e5');
});

it('keeps an empty accent block in the head for a live change, and leaves it out with accents off', function (): void {
    withAccents();

    expect($this->get(Filament::getPanel('admin')->getUrl())->getContent())->toContain('<style id="nitpick-accent"></style>');
});

it('keeps the user\'s theme when the accent changes, and the accent when the theme does', function (): void {
    withAccents();
    $plugin = pluginFor('admin');

    $plugin->setUserTheme('midnight');
    $plugin->setUserAccent('rose');
    $plugin->setUserTheme(null);

    expect(ThemePreference::query()->where('user_id', $this->user->id)->first())
        ->theme_id->toBeNull()
        ->accent->toBe('rose');

    $plugin->setUserAccent(null);

    expect(ThemePreference::query()->where('user_id', $this->user->id)->exists())->toBeFalse();
});

it('goes back to the theme\'s colour', function (): void {
    withAccents();
    pluginFor('admin')->setUserAccent('rose');

    livewire(Appearance::class)
        ->assertSet('data.accent', 'rose')
        ->set('data.accent', AccentPicker::THEME);

    expect(pluginFor('admin')->getUserAccent())->toBeNull()
        ->and(pluginFor('admin')->getColorsFor(app(ThemeManager::class)->resolve('aurora'))['primary'])->toBe(Color::Sky);
});

it('refuses a colour too light to stand out, says why, and keeps the previous one', function (): void {
    withAccents();
    pluginFor('admin')->setUserAccent('rose');

    livewire(Appearance::class)
        ->set('data.accent', 'yellow')
        ->assertSet('data.accent', 'rose')
        ->assertNotified(__('nitpick::themes.accent.refused'));

    expect(pluginFor('admin')->getUserAccent())->toBe('rose');
});

it('checks every accent against WCAG AA for interface parts', function (string $accent, ?string $problem): void {
    expect(Accent::problem($accent))->toBe($problem);
})->with([
    'a Filament colour' => ['indigo', null],
    'a hex colour' => ['#0ea5e9', null],
    'too light on white' => ['yellow', 'nitpick::themes.accent.too_light'],
    'an unknown name' => ['chartreuse', 'nitpick::themes.accent.invalid'],
    'a short hex' => ['#fff', 'nitpick::themes.accent.invalid'],
    'CSS' => ['red; background: url(x)', 'nitpick::themes.accent.invalid'],
]);

it('offers only the colours that pass, and no grays', function (): void {
    expect(Accent::swatches())->toHaveKey('indigo')
        ->not->toHaveKey('yellow')
        ->not->toHaveKey('zinc');
});

it('keeps an accent in the session store too', function (): void {
    config()->set('nitpick.store', 'session');
    withAccents();

    pluginFor('admin')->setUserTheme('midnight');
    pluginFor('admin')->setUserAccent('teal');

    expect(pluginFor('admin')->getUserAccent())->toBe('teal')
        ->and(pluginFor('admin')->getUserTheme()?->getId())->toBe('midnight');
});

it('offers no accents with a store of your own that keeps none', function (): void {
    withAccents();
    app()->bind(ThemePreferenceStore::class, fn () => new class implements ThemePreferenceStore
    {
        public function get($user, string $panelId): ?string
        {
            return null;
        }

        public function set($user, string $panelId, ?string $themeId): void {}
    });

    expect(pluginFor('admin')->hasAccent())->toBeFalse();
});

it('paints the previews\' buttons with the accent, over the bundled themes\' own gradients', function (): void {
    withAccents();
    pluginFor('admin')->setUserAccent('rose');

    $html = $this->get(Appearance::getUrl())->getContent();

    expect($html)->toContain('--nitpick-preview-primary: '.Color::Rose[600].';')
        ->not->toContain('--nitpick-preview-primary: linear-gradient(135deg, #0ea5e9, #06b6d4);');
});

it('turns the bundled themes\' gradients to the accent while one is set', function (string $theme): void {
    $css = file_get_contents(app(ThemeManager::class)->resolve($theme)->getStylesheets()[0]);

    expect($css)->toContain('[data-nitpick-theme="'.$theme.'"]:has(#nitpick-accent:not(:empty)) {')
        ->toMatch('/:has\(#nitpick-accent:not\(:empty\)\) \{\s+--nitpick-[a-z-]+gradient: linear-gradient\(135deg, var\(--primary-/');
})->with(['aurora', 'midnight']);
