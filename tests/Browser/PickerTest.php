<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Artisan;
use Packstub\Nitpick\Facades\Nitpick;
use Packstub\Nitpick\Theme;

// The picker in a real browser: nitpick.js loaded from the published assets, the cover, the swap, a pick
// that reloads the page and reopens the modal, SPA navigation and reduced motion. Run with
// `composer test:browser` (Playwright: `npm install && npx playwright install chromium`).

beforeEach(function (): void {
    // The panel's stylesheets and scripts, Nitpick's among them, where the browser asks for them.
    Artisan::call('filament:assets');

    $this->user = createUser();
    $this->actingAs($this->user);
});

function card(string $theme): string
{
    return 'label.nitpick-option:has(input[value="'.$theme.'"])';
}

it('changes the theme live from the Appearance page, with its light/dark mode, and saves it', function (): void {
    $page = visit('/admin/appearance');

    $page->assertScript('document.documentElement.dataset.nitpickTheme', 'aurora')
        ->assertScript('typeof window.Nitpick.pick', 'function')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->click(card('midnight'))
        ->wait(1.5)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight')
        ->assertScript("document.getElementById('nitpick-theme-active').getAttribute('href').includes('nitpick-theme-midnight')", true)
        // Midnight is made for dark mode, and a pick takes it.
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        // The cover is gone and the panel's own transitions are back.
        ->assertScript("document.getElementById('nitpick-cover-layer') === null", true)
        ->assertScript("document.documentElement.hasAttribute('data-nitpick-still')", false)
        ->assertNoJavaScriptErrors();

    expect(pluginFor('admin')->getUserTheme($this->user)?->getId())->toBe('midnight');
});

it('reloads under the cover for a theme that configures the panel, and opens the picker again', function (): void {
    Nitpick::register(Theme::make('rail')->panel(fn (Panel $panel) => $panel->topNavigation()));

    $page = visit('/admin');

    $page->assertScript("document.body.classList.contains('fi-body-has-top-navigation')", false)
        ->click('.fi-user-menu-trigger')
        ->click('Appearance')
        ->wait(0.5)
        ->click(card('rail'))
        ->wait(3.5)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'rail')
        ->assertScript("document.body.classList.contains('fi-body-has-top-navigation')", true)
        // The modal it was picked from is open again, and the cover has lifted.
        ->assertScript("document.querySelector('.fi-modal .nitpick-picker') !== null", true)
        ->assertScript("document.documentElement.hasAttribute('data-nitpick-cover')", false)
        ->assertScript("document.getElementById('nitpick-cover-layer') === null", true)
        ->assertNoJavaScriptErrors();

    expect(pluginFor('admin')->getUserTheme($this->user)?->getId())->toBe('rail');
});

it('keeps the theme and the picker working across SPA navigation', function (): void {
    Filament::getPanel('admin')->spa();
    pluginFor('admin')->setUserTheme('midnight', $this->user);

    $page = visit('/admin/appearance');

    // To the dashboard and back without a page load: Livewire swaps the body and merges the head.
    $page->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight')
        ->assertScript('window.__loadedOnce = true')
        ->click('Dashboard')
        ->wait(1)
        ->assertScript("window.location.pathname === '/admin' && window.__loadedOnce === true", true)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight');

    $page->script("Livewire.navigate('/admin/appearance')");

    $page->wait(1)
        ->assertScript("window.location.pathname === '/admin/appearance' && window.__loadedOnce === true", true)
        ->click(card('aurora'))
        ->wait(1.5)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'aurora')
        ->assertNoJavaScriptErrors();
});

it('changes the theme at once, without the cover, for visitors who prefer reduced motion', function (): void {
    $page = visit('/admin/appearance', ['reducedMotion' => 'reduce']);

    $page->assertScript('window.Nitpick.transition()', null)
        ->click(card('midnight'))
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight')
        ->assertScript("document.getElementById('nitpick-cover-layer') === null", true)
        ->assertNoJavaScriptErrors();
});

it('goes back to the panel default from the "Panel default" card', function (): void {
    pluginFor('admin')->setUserTheme('midnight', $this->user);

    $page = visit('/admin/appearance');

    $page->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight')
        ->click(card('_default'))
        ->wait(1.5)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'aurora')
        ->assertNoJavaScriptErrors();

    expect(pluginFor('admin')->getUserTheme($this->user))->toBeNull();
});

it('applies an accent colour at once, and keeps it after a reload', function (): void {
    pluginFor('admin')->picker(accent: true);

    $page = visit('/admin/appearance');
    $primary = "getComputedStyle(document.documentElement).getPropertyValue('--primary-500').trim()";

    $page->assertScript("document.getElementById('nitpick-accent').textContent", '')
        ->click('label.nitpick-accent:has(input[value="rose"])')
        ->wait(1)
        ->assertScript("document.getElementById('nitpick-accent').textContent.includes('--primary-500')", true)
        ->assertScript($primary, Color::Rose[500])
        // Aurora's gradient buttons follow the accent too.
        ->assertScript("getComputedStyle(document.documentElement).getPropertyValue('--nitpick-primary-gradient').includes('#0ea5e9')", false)
        ->assertNoJavaScriptErrors();

    expect(pluginFor('admin')->getUserAccent($this->user))->toBe('rose');

    visit('/admin')->assertScript($primary, Color::Rose[500]);
});

it('lets a manager set the theme for everyone, which their own panel follows', function (): void {
    pluginFor('admin')->picker(manage: true);

    $page = visit('/admin/appearance');

    $page->assertSee('For everyone')
        ->click('label.nitpick-option:has(input[name="data.panel_theme"][value="midnight"])')
        ->wait(1.5)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight')
        ->assertNoJavaScriptErrors();

    expect(pluginFor('admin')->getPanelTheme()?->themeId)->toBe('midnight');
});

it('mirrors the swap on a right-to-left panel', function (): void {
    app()->setLocale('ar');
    pluginFor('admin')->picker(transition: 'slide');

    $page = visit('/admin/appearance');

    $page->assertScript('document.documentElement.dir', 'rtl')
        ->click(card('midnight'))
        ->wait(0.15)
        ->assertScript("(document.getElementById('nitpick-cover-layer') || {}).style.transform", 'scaleX(-1)')
        ->wait(1.5)
        ->assertScript('document.documentElement.dataset.nitpickTheme', 'midnight')
        ->assertNoJavaScriptErrors();
});
