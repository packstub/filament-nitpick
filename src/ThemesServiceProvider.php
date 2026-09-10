<?php

namespace Packstub\Themes;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Packstub\Themes\Contracts\ThemePreferenceStore;
use Packstub\Themes\Stores\DatabaseThemePreferenceStore;
use Packstub\Themes\Stores\SessionThemePreferenceStore;
use Packstub\Themes\Themes\Aurora;
use Packstub\Themes\Themes\Midnight;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'packstub-themes';

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
                    ->askToStarRepoOnGitHub('packstub/filament-themes');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ThemeManager::class);

        $this->app->bind(ThemePreferenceStore::class, function (): ThemePreferenceStore {
            return match (config('packstub-themes.store', 'database')) {
                'session' => new SessionThemePreferenceStore,
                default => new DatabaseThemePreferenceStore,
            };
        });
    }

    public function packageBooted(): void
    {
        $this->app->make(ThemeManager::class)->register(Aurora::class, Midnight::class);

        FilamentAsset::register([
            Css::make('packstub-themes', __DIR__.'/../resources/css/packstub-themes.css'),
        ], package: ThemeManager::ASSET_PACKAGE);
    }
}
