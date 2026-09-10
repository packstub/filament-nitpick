<?php

namespace Packstub\Themes\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Packstub\Themes\Tests\Fixtures\AdminPanelProvider;
use Packstub\Themes\Tests\Fixtures\CustomerPanelProvider;
use Packstub\Themes\Tests\Fixtures\Ocean;
use Packstub\Themes\Tests\Fixtures\User;
use Packstub\Themes\ThemeManager;
use Packstub\Themes\ThemesServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();

        app(ThemeManager::class)->register(Ocean::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            // Real apps discover filament/* before livewire; Filament binds its DataStore
            // override first and Livewire pins the shared instance. Keep that order.
            LivewireServiceProvider::class,
            ThemesServiceProvider::class,
            AdminPanelProvider::class,
            CustomerPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:2fl+Ktv6fZ7c7ZQfF1Zt6Q0Wd9jz5bJ6rKq8nX0m3Yk=');
        $app['config']->set('view.paths', [__DIR__.'/Fixtures/views']);
    }

    protected function migrate(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        (include __DIR__.'/../database/migrations/create_theme_preferences_table.php.stub')->up();
    }
}
