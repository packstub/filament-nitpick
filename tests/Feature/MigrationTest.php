<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function runPreferencesMigration(): void
{
    (include __DIR__.'/../../database/migrations/create_nitpick_theme_preferences_table.php.stub')->up();
}

it('keeps choices in nitpick_theme_preferences on a new install', function (): void {
    expect(config('nitpick.table'))->toBe('nitpick_theme_preferences')
        ->and(Schema::hasTable('nitpick_theme_preferences'))->toBeTrue();
});

it('leaves a beta install table alone when the migration is published again', function (): void {
    Schema::create('theme_preferences', function (Blueprint $table): void {
        $table->id();
        $table->morphs('user');
        $table->string('panel_id');
        $table->string('theme_id');
        $table->timestamps();
    });
    config()->set('nitpick.table', 'theme_preferences');

    runPreferencesMigration();
    runPreferencesMigration();

    pluginFor('admin')->setUserTheme('midnight', createUser());

    expect(Schema::hasTable('theme_preferences'))->toBeTrue()
        ->and(DB::table('theme_preferences')->value('theme_id'))->toBe('midnight');
});
