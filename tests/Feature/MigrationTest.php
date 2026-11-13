<?php

use Illuminate\Auth\GenericUser;
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

it('types user_id after the users\' keys', function (?string $type, string $column): void {
    Schema::drop('nitpick_theme_preferences');
    config()->set('nitpick.user_key_type', $type);

    runPreferencesMigration();

    expect(Schema::getColumnType('nitpick_theme_preferences', 'user_id'))->toBe($column);
})->with([
    'the app default' => [null, 'integer'],
    'int' => ['int', 'integer'],
    'uuid' => ['uuid', 'varchar'],
    'ulid' => ['ulid', 'varchar'],
]);

it('follows the app\'s morph key type when none is set', function (): void {
    Schema::drop('nitpick_theme_preferences');
    Schema::morphUsingUuids();

    try {
        runPreferencesMigration();
    } finally {
        Schema::defaultMorphKeyType('int');
    }

    expect(Schema::getColumnType('nitpick_theme_preferences', 'user_id'))->toBe('varchar');
});

it('saves a choice for a user with a UUID key', function (): void {
    Schema::drop('nitpick_theme_preferences');
    config()->set('nitpick.user_key_type', 'uuid');
    runPreferencesMigration();

    $user = new GenericUser(['id' => '9b2f7c1e-4d3a-4f8e-9a61-2c5d8e7f1a0b']);
    pluginFor('admin')->setUserTheme('midnight', $user);

    expect(pluginFor('admin')->getUserTheme($user)?->getId())->toBe('midnight');
});

it('refuses an unknown user key type', function (): void {
    Schema::drop('nitpick_theme_preferences');
    config()->set('nitpick.user_key_type', 'string');

    runPreferencesMigration();
})->throws(InvalidArgumentException::class, 'user_key_type');

it('adds the accent to a table created by a beta, and lets a row hold an accent without a theme', function (): void {
    Schema::drop('nitpick_theme_preferences');
    Schema::create('nitpick_theme_preferences', function (Blueprint $table): void {
        $table->id();
        $table->morphs('user');
        $table->string('panel_id');
        $table->string('theme_id');
        $table->timestamps();
        $table->unique(['user_type', 'user_id', 'panel_id']);
    });

    $add = fn () => (include __DIR__.'/../../database/migrations/add_accent_to_nitpick_theme_preferences_table.php.stub')->up();
    $add();
    $add();

    expect(Schema::hasColumn('nitpick_theme_preferences', 'accent'))->toBeTrue();

    pluginFor('admin')->picker(accent: true)->setUserAccent('teal', createUser());

    expect(DB::table('nitpick_theme_preferences')->value('accent'))->toBe('teal')
        ->and(DB::table('nitpick_theme_preferences')->value('theme_id'))->toBeNull();
});

it('finishes the accent migration when a run stopped after adding the column', function (): void {
    Schema::drop('nitpick_theme_preferences');
    Schema::create('nitpick_theme_preferences', function (Blueprint $table): void {
        $table->id();
        $table->morphs('user');
        $table->string('panel_id');
        $table->string('theme_id');
        $table->string('accent')->nullable();
        $table->timestamps();
    });

    (include __DIR__.'/../../database/migrations/add_accent_to_nitpick_theme_preferences_table.php.stub')->up();

    expect(collect(Schema::getColumns('nitpick_theme_preferences'))->firstWhere('name', 'theme_id')['nullable'])->toBeTrue();
});

it('leaves a beta install table alone when the migration is rolled back', function (): void {
    Schema::create('theme_preferences', function (Blueprint $table): void {
        $table->id();
        $table->morphs('user');
        $table->string('panel_id');
        $table->string('theme_id');
        $table->timestamps();
    });
    config()->set('nitpick.table', 'theme_preferences');

    runPreferencesMigration();
    (include __DIR__.'/../../database/migrations/create_nitpick_theme_preferences_table.php.stub')->down();

    expect(Schema::hasTable('theme_preferences'))->toBeTrue();
});
