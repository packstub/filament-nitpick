<?php

use Filament\Panel;
use Packstub\Themes\Tests\Fixtures\Ocean;
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
        ->toContain('html[data-packstub-theme="midnight"] { --primary-50: oklch(')
        ->toContain('html[data-packstub-theme="ocean"] {')
        ->toContain('"midnight":{"href":"')
        ->toContain('"ocean":{"href":null')
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
