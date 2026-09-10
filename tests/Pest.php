<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Packstub\Themes\Tests\Fixtures\User;
use Packstub\Themes\Tests\TestCase;
use Packstub\Themes\ThemesPlugin;

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
function pluginFor(string $panelId): ThemesPlugin
{
    $panel = Filament::getPanel($panelId);

    Filament::setCurrentPanel($panel);

    /** @var ThemesPlugin $plugin */
    $plugin = $panel->getPlugin(ThemesPlugin::ID);

    return $plugin;
}
