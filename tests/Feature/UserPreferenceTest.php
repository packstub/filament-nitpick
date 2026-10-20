<?php

use Illuminate\Support\Facades\Event;
use Packstub\Nitpick\Events\ThemeChanged;
use Packstub\Nitpick\Models\ThemePreference;
use Packstub\Nitpick\Themes\Midnight;

it('uses the panel default until the user picks a theme', function (): void {
    $this->actingAs(createUser());

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('aurora');
});

it('saves the signed-in user choice for the current panel', function (): void {
    Event::fake([ThemeChanged::class]);
    $user = createUser();
    $this->actingAs($user);

    $plugin = pluginFor('admin');
    $plugin->setUserTheme('midnight');

    expect($plugin->getActiveTheme())->toBeInstanceOf(Midnight::class)
        ->and(ThemePreference::query()->where('panel_id', 'admin')->where('user_id', $user->id)->value('theme_id'))->toBe('midnight');

    Event::assertDispatched(ThemeChanged::class, fn (ThemeChanged $event): bool => $event->panelId === 'admin' && $event->theme?->getId() === 'midnight');
});

it('keeps the choice per panel', function (): void {
    $this->actingAs(createUser());

    pluginFor('admin')->setUserTheme('midnight');

    expect(pluginFor('customer')->getActiveTheme()->getId())->toBe('midnight');

    pluginFor('customer');
    config()->set('nitpick.default', 'ocean');

    // The customer panel offers Midnight only and is not user-selectable,
    // so a choice saved elsewhere never leaks into it.
    expect(pluginFor('customer')->getUserTheme())->toBeNull();
});

it('clears the choice with null so the default applies again', function (): void {
    $this->actingAs(createUser());

    $plugin = pluginFor('admin');
    $plugin->setUserTheme(Midnight::class);
    $plugin->setUserTheme(null);

    expect($plugin->getActiveTheme()->getId())->toBe('aurora')
        ->and(ThemePreference::query()->count())->toBe(0);
});

it('refuses a theme the panel does not offer', function (): void {
    $this->actingAs(createUser());

    pluginFor('customer')->setUserTheme('aurora');
})->throws(InvalidArgumentException::class, 'not offered');

it('ignores a stored choice when user selection is turned off', function (): void {
    $user = createUser();
    $this->actingAs($user);

    pluginFor('admin')->setUserTheme('midnight');
    config()->set('nitpick-test.selectable', false);

    $plugin = pluginFor('admin');

    expect($plugin->getUserTheme())->toBeNull()
        ->and($plugin->getActiveTheme()->getId())->toBe('aurora');
});

it('ignores a stored choice for a theme no longer offered', function (): void {
    $user = createUser();
    $this->actingAs($user);

    ThemePreference::query()->create([
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'panel_id' => 'admin',
        'theme_id' => 'retired',
    ]);

    expect(pluginFor('admin')->getActiveTheme()->getId())->toBe('aurora');
});

it('throws when saving without a signed-in user', function (): void {
    pluginFor('admin')->setUserTheme('midnight');
})->throws(LogicException::class, 'no signed-in user');

it('can keep the choice in the session instead of the database', function (): void {
    config()->set('nitpick.store', 'session');
    $this->actingAs(createUser());

    $plugin = pluginFor('admin');
    $plugin->setUserTheme('midnight');

    expect($plugin->getActiveTheme()->getId())->toBe('midnight')
        ->and(ThemePreference::query()->count())->toBe(0)
        ->and(session()->all())->toHaveKey('nitpick');
});
