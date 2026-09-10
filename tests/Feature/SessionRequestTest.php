<?php

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Packstub\Themes\Contracts\ThemePreferenceStore;

// Filament boots the panel (SetUpPanel) before StartSession runs, so the user
// is not known when the colours are first registered. These tests
// authenticate the way a browser does, through the session, rather than with
// actingAs(), which would hide that ordering.

it('applies a saved choice on a request authenticated through the session', function (): void {
    $user = createUser();
    pluginFor('admin');
    app(ThemePreferenceStore::class)->set($user, 'admin', 'midnight');

    $this->withSession([auth()->guard('web')->getName() => $user->getAuthIdentifier()])
        ->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="midnight">', false)
        ->assertSee('--default-theme-mode: dark', false)
        ->assertSee('--primary-500:'.Color::Violet[500], false);
});

it('renders the default theme for a signed-out visitor', function (): void {
    $this->get(Filament::getPanel('admin')->getLoginUrl())
        ->assertOk()
        ->assertSee('<meta name="packstub-theme" content="aurora">', false)
        ->assertSee('--default-theme-mode: light', false)
        ->assertSee('--primary-500:'.Color::Sky[500], false);
});
