<?php

use Filament\Panel;
use Illuminate\Support\Facades\Vite;
use Packstub\Themes\Facades\Themes;
use Packstub\Themes\Tests\Fixtures\Ocean;
use Packstub\Themes\Tests\Fixtures\SlateThemePlugin;
use Packstub\Themes\Tests\Fixtures\TideThemePlugin;
use Packstub\Themes\Theme;
use Packstub\Themes\Themes\Midnight;
use Packstub\Themes\ThemesPlugin;

function headFor(ThemesPlugin $plugin): string
{
    $plugin->register(Panel::make()->id('head-test'));

    return $plugin->renderHead();
}

it('renders the active theme meta, stylesheet link and bootstrap script', function (): void {
    $head = headFor(ThemesPlugin::make()->default(Midnight::class));

    expect($head)
        ->toContain('<meta name="packstub-theme" content="midnight">')
        ->toContain('id="packstub-theme-active"')
        ->toContain('packstub-theme-midnight.css')
        ->toContain('data-navigate-track')
        ->toContain('window.PackstubThemes')
        ->toContain("document.addEventListener('livewire:navigated', stamp)");
});

it('renders no stylesheet link for a palette-only theme', function (): void {
    $head = headFor(ThemesPlugin::make()->default(Ocean::class));

    expect($head)
        ->toContain('content="ocean"')
        ->not->toContain('id="packstub-theme-active"');
});

it('never writes a fixed light or dark mode on page load', function (): void {
    $head = headFor(ThemesPlugin::make()->default(Midnight::class)->userSelectable());

    // The user's toggle is theirs. The only write is inside preview(), with
    // the previewed theme's mode as a variable.
    expect($head)
        ->not->toContain("localStorage.setItem('theme', '")
        ->toContain("localStorage.setItem('theme', mode)");
});

it('omits the palettes and manifest unless users may switch', function (): void {
    $head = headFor(ThemesPlugin::make());

    expect($head)
        ->not->toContain('packstub-themes-palettes')
        ->toContain('var manifest = null;');
});

it('renders every offered palette and a manifest when users may switch', function (): void {
    $head = headFor(ThemesPlugin::make()->themes([Midnight::class, Ocean::class])->userSelectable());

    expect($head)
        ->toContain('<style id="packstub-themes-palettes">')
        ->toContain("\n[data-packstub-theme=\"ocean\"] {")
        ->toContain('>[data-packstub-theme="midnight"] { --primary-50: oklch(')
        ->toContain('"midnight":{"hrefs":["')
        ->toContain('"ocean":{"hrefs":[]')
        ->toContain('"mode":"dark"')
        ->not->toContain('data-packstub-theme="aurora"');
});

it('links the custom stylesheet after the theme', function (): void {
    $head = headFor(ThemesPlugin::make()->customCss('css/overrides.css'));
    $remote = headFor(ThemesPlugin::make()->customCss('https://cdn.example.com/x.css'));

    expect(strpos($head, 'packstub-theme-custom'))->toBeGreaterThan(strpos($head, 'packstub-theme-active'))
        ->and($head)->toContain('href="http://localhost/css/overrides.css"')
        ->and($remote)->toContain('href="https://cdn.example.com/x.css"');
});

it('links every stylesheet of a theme made of several files, in order', function (): void {
    Themes::register(
        Theme::make('slate')->plugin(fn (): SlateThemePlugin => SlateThemePlugin::make()),
    );

    $head = headFor(ThemesPlugin::make()->themes(['slate', Ocean::class])->userSelectable());

    expect(substr_count($head, 'data-packstub-theme-stylesheet data-navigate-track'))->toBe(3)
        ->and(substr_count($head, 'id="packstub-theme-active"'))->toBe(1)
        ->and(strpos($head, 'href="https://fonts.example.com/slate.css"'))->toBeGreaterThan(strpos($head, 'packstub-theme-slate.css'))
        ->and(strpos($head, 'packstub-theme-slate--3.css'))->toBeGreaterThan(strpos($head, 'href="https://fonts.example.com/slate.css"'))
        ->and($head)->toMatch('/"slate":\{"hrefs":\["[^"]+packstub-theme-slate\.css[^"]*","https:\/\/fonts\.example\.com\/slate\.css","[^"]+packstub-theme-slate--3\.css[^"]*"\]/');
});

it('links a Vite theme from the app\'s build', function (): void {
    Vite::shouldReceive('asset')->with(TideThemePlugin::ENTRY)->andReturn('https://app.test/build/assets/theme-abc123.css');

    Themes::register(Theme::make('tide')->plugin(fn (): TideThemePlugin => TideThemePlugin::make()));

    $head = headFor(ThemesPlugin::make()->default('tide'));

    expect($head)->toContain('id="packstub-theme-active" href="https://app.test/build/assets/theme-abc123.css" data-packstub-theme="tide"');
});

it('still renders when a Vite theme has not been built yet', function (): void {
    Themes::register(Theme::make('tide')->plugin(fn (): TideThemePlugin => TideThemePlugin::make()));

    $head = headFor(ThemesPlugin::make()->default('tide'));

    expect($head)
        ->toContain('<meta name="packstub-theme" content="tide">')
        ->not->toContain('data-packstub-theme-stylesheet data-navigate-track');
});

it('renders the palettes ahead of the theme stylesheets, at the weight of Filament\'s own', function (): void {
    $head = headFor(ThemesPlugin::make()->default(Midnight::class)->userSelectable());

    expect(strpos($head, 'packstub-themes-palettes'))->toBeLessThan(strpos($head, 'id="packstub-theme-active"'))
        ->and($head)->not->toContain('html[data-packstub-theme=');
});
