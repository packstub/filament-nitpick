<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\User;
use Packstub\Nitpick\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

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
