<?php

namespace Packstub\Nitpick\Support;

use Filament\Panel;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use LogicException;
use Packstub\Nitpick\Contracts\ConfiguresPanel;
use Packstub\Nitpick\Contracts\Theme;

/**
 * Runs a theme's servingPanel() on a panel Filament has already booted. What
 * the panel reads while rendering (top navigation, navigation items, fonts)
 * needs nothing more; what Filament registers when it boots a panel is
 * registered here for whatever the theme added: render hooks and assets,
 * and an added plugin is booted. The colours it set are returned, for the
 * caller to register in their place among the panel's palettes.
 *
 * Pages, resources and widgets cannot join a panel mid-request (their
 * routes and Livewire components are registered before it): any the theme
 * added are taken back out and reported, so the panel keeps rendering.
 */
class PanelServing
{
    /**
     * @return array<string, array<int | string, string | int> | string>
     */
    public static function run(ConfiguresPanel $theme, Panel $panel): array
    {
        $before = static::state($panel);

        $theme->servingPanel($panel);

        $after = static::state($panel);

        static::keepComponents($theme, $panel, $before['components'], $after['components']);

        foreach ($after['renderHooks'] as $name => $scopes) {
            foreach ($scopes as $scope => $hooks) {
                foreach (array_slice($hooks, count($before['renderHooks'][$name][$scope] ?? [])) as $hook) {
                    FilamentView::registerRenderHook($name, $hook, $scope);
                }
            }
        }

        foreach ($after['assets'] as $package => $assets) {
            $added = array_slice($assets, count($before['assets'][$package] ?? []));

            if ($added !== []) {
                FilamentAsset::register($added, $package);
            }
        }

        foreach (array_diff_key($panel->getPlugins(), $before['plugins']) as $plugin) {
            $plugin->boot($panel);
        }

        $colors = [];

        foreach (array_slice($after['colors'], count($before['colors'])) as $set) {
            $colors = [...$colors, ...(array) $panel->evaluate($set)];
        }

        return $colors;
    }

    /**
     * @param  array<string, array<mixed>>  $before
     * @param  array<string, array<mixed>>  $after
     */
    protected static function keepComponents(ConfiguresPanel $theme, Panel $panel, array $before, array $after): void
    {
        if ($before === $after) {
            return;
        }

        (function (array $components): void {
            foreach ($components as $property => $value) {
                $this->{$property} = $value;
            }
        })->call($panel, $before);

        $id = $theme instanceof Theme ? $theme->getId() : $theme::class;

        report(new LogicException("The theme [{$id}] added pages, resources or widgets to the [{$panel->getId()}] panel while serving it; they were left out, because their routes are registered before the request. Add the plugin that brings them to the panel itself, or turn its pages off."));
    }

    /**
     * @return array{renderHooks: array<string, array<string, array<int, mixed>>>, assets: array<string, array<int, mixed>>, colors: array<int, mixed>, plugins: array<string, mixed>, components: array<string, array<mixed>>}
     */
    protected static function state(Panel $panel): array
    {
        return (fn (): array => [
            'renderHooks' => $this->renderHooks,
            'assets' => $this->assets,
            'colors' => $this->colors,
            'plugins' => $this->plugins,
            'components' => [
                'pages' => $this->pages,
                'pageConfigurations' => $this->pageConfigurations,
                'resources' => $this->resources,
                'resourceConfigurations' => $this->resourceConfigurations,
                'widgets' => $this->widgets,
                'clusteredComponents' => $this->clusteredComponents,
                'livewireComponents' => $this->livewireComponents,
            ],
        ])->call($panel);
    }
}
