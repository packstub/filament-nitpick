<?php

namespace Packstub\Nitpick;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Packstub\Nitpick\Contracts\ThemePreferenceStore;
use Packstub\Nitpick\Stores\DatabaseThemePreferenceStore;
use Packstub\Nitpick\Stores\SessionThemePreferenceStore;
use Packstub\Nitpick\Themes\Aurora;
use Packstub\Nitpick\Themes\Midnight;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NitpickServiceProvider extends PackageServiceProvider
{
    public static string $name = 'nitpick';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$name)
            ->hasTranslations()
            ->hasMigrations(['create_theme_preferences_table'])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('packstub/filament-nitpick');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ThemeManager::class);

        $this->app->bind(ThemePreferenceStore::class, function (): ThemePreferenceStore {
            return match (config('nitpick.store', 'database')) {
                'session' => new SessionThemePreferenceStore,
                default => new DatabaseThemePreferenceStore,
            };
        });
    }

    public function packageBooted(): void
    {
        $this->app->make(ThemeManager::class)->register(Aurora::class, Midnight::class);

        FilamentAsset::register([
            Css::make('nitpick', __DIR__.'/../resources/css/nitpick.css'),
        ], package: ThemeManager::ASSET_PACKAGE);
    }
}
