<?php

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Notifications\NotificationMailbox;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\User;
use Packstub\Nitpick\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit', 'Browser');

/**
 * @param  array<string, mixed>  $attributes
 */
function createUser(array $attributes = []): User
{
    static $sequence = 0;

    $sequence++;

    return User::query()->create([
        'name' => "User {$sequence}",
        'email' => "user{$sequence}@example.com",
        'password' => Hash::make('secret'),
        ...$attributes,
    ]);
}

/**
 * Make a registered panel current and return its themes plugin.
 */
function pluginFor(string $panelId): NitpickPlugin
{
    $panel = Filament::getPanel($panelId);

    Filament::setCurrentPanel($panel);

    /** @var NitpickPlugin $plugin */
    $plugin = $panel->getPlugin(NitpickPlugin::ID);

    return $plugin;
}

/**
 * The warning notice was sent with this page. Before Filament 5.10.2 a notice
 * sent while serving a page lives in the session and renders with it; since,
 * it goes to Filament's mailbox and the page fetches it once loaded, which is
 * where assertNotified() looks.
 */
function expectNotice(TestResponse $response, string $title, string $body): void
{
    if (class_exists(NotificationMailbox::class)) {
        Notification::assertNotified(Notification::make()->warning()->title($title)->body($body));

        return;
    }

    $response->assertSee($title)->assertSee($body);
}
