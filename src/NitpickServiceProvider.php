<?php

namespace Packstub\Nitpick;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
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

    /**
     * Filament's commands that publish assets: filament:upgrade (run by
     * Composer after every update) and filament:install call filament:assets
     * from inside, where no event marks it.
     *
     * @var array<int, string>
     */
    public const PUBLISHING_COMMANDS = ['filament:assets', 'filament:upgrade', 'filament:install'];

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

        // A theme's plugin joins the panel only while the theme is active, so its assets are
        // handed to Filament's publishing commands here.
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (in_array($event->command, static::PUBLISHING_COMMANDS, true)) {
                $this->app->make(ThemeManager::class)->registerPanelAssets();
            }
        });
    }
}
