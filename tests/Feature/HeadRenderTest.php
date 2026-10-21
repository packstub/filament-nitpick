<?php

use Filament\Panel;
use Illuminate\Support\Facades\Vite;
use Packstub\Nitpick\Enums\Transition;
use Packstub\Nitpick\Facades\Themes;
use Packstub\Nitpick\NitpickPlugin;
use Packstub\Nitpick\Tests\Fixtures\Ocean;
use Packstub\Nitpick\Tests\Fixtures\SlateThemePlugin;
use Packstub\Nitpick\Tests\Fixtures\TideThemePlugin;
use Packstub\Nitpick\Theme;
use Packstub\Nitpick\Themes\Midnight;

function headFor(NitpickPlugin $plugin): string
{
    $plugin->register(Panel::make()->id('head-test'));

    return $plugin->renderHead();
}

it('renders the active theme meta, stylesheet link and bootstrap script', function (): void {
    $head = headFor(NitpickPlugin::make()->default(Midnight::class));

    expect($head)
        ->toContain('<meta name="packstub-theme" content="midnight">')
        ->toContain('id="packstub-theme-active"')
        ->toContain('packstub-theme-midnight.css')
        ->toContain('data-navigate-track')
        ->toContain('window.PackstubThemes')
        ->toContain("document.addEventListener('livewire:navigated', stamp)");
});

it('renders no stylesheet link for a palette-only theme', function (): void {
    $head = headFor(NitpickPlugin::make()->default(Ocean::class));

    expect($head)
        ->toContain('content="ocean"')
        ->not->toContain('id="packstub-theme-active"');
});

it('never writes a fixed light or dark mode on page load', function (): void {
    $head = headFor(NitpickPlugin::make()->default(Midnight::class)->picker());

    // The user's toggle is theirs. The only write is inside preview(), with
    // the previewed theme's mode as a variable.
    expect($head)
        ->not->toContain("localStorage.setItem('theme', '")
        ->not->toContain("localStorage.setItem('filament:theme:mode', '")
        ->toContain("localStorage.setItem('filament:theme:mode', mode)")
        ->toContain("localStorage.setItem('theme', mode)");
});

it('points the user menu\'s light/dark switcher at the mode a preview applies', function (): void {
    $head = headFor(NitpickPlugin::make()->default(Midnight::class)->picker());

    expect($head)
        ->toContain("document.querySelectorAll('.fi-theme-switcher')")
        ->toContain('data.theme = mode;');
});

it('omits the palettes and manifest unless users may switch', function (): void {
    $head = headFor(NitpickPlugin::make());

    expect($head)
        ->not->toContain('nitpick-palettes')
        ->toContain('var manifest = null;');
});

it('renders every offered palette and a manifest when users may switch', function (): void {
    $head = headFor(NitpickPlugin::make()->themes([Midnight::class, Ocean::class])->picker());

    expect($head)
        ->toContain('<style id="nitpick-palettes">')
        ->toContain("\n[data-packstub-theme=\"ocean\"] {")
        ->toContain('>[data-packstub-theme="midnight"] { --primary-50: oklch(')
        ->toContain('"midnight":{"hrefs":["')
        ->toContain('"ocean":{"hrefs":[]')
        ->toContain('"mode":"dark"')
        ->not->toContain('data-packstub-theme="aurora"');
});

it('links the custom stylesheet after the theme', function (): void {
    $head = headFor(NitpickPlugin::make()->customCss('css/overrides.css'));
    $remote = headFor(NitpickPlugin::make()->customCss('https://cdn.example.com/x.css'));

    expect(strpos($head, 'packstub-theme-custom'))->toBeGreaterThan(strpos($head, 'packstub-theme-active'))
        ->and($head)->toContain('href="http://localhost/css/overrides.css"')
        ->and($remote)->toContain('href="https://cdn.example.com/x.css"');
});

it('links every stylesheet of a theme made of several files, in order', function (): void {
    Themes::register(
        Theme::make('slate')->stylesheets([SlateThemePlugin::path('slate.css'), 'https://fonts.example.com/slate.css', SlateThemePlugin::path('slate-corners.css')]),
    );

    $head = headFor(NitpickPlugin::make()->themes(['slate', Ocean::class])->picker());

    expect(substr_count($head, 'data-packstub-theme-stylesheet data-navigate-track'))->toBe(3)
        ->and(substr_count($head, 'id="packstub-theme-active"'))->toBe(1)
        ->and(strpos($head, 'href="https://fonts.example.com/slate.css"'))->toBeGreaterThan(strpos($head, 'packstub-theme-slate.css'))
        ->and(strpos($head, 'packstub-theme-slate--3.css'))->toBeGreaterThan(strpos($head, 'href="https://fonts.example.com/slate.css"'))
        ->and($head)->toMatch('/"slate":\{"hrefs":\["[^"]+packstub-theme-slate\.css[^"]*","https:\/\/fonts\.example\.com\/slate\.css","[^"]+packstub-theme-slate--3\.css[^"]*"\]/');
});

it('links a Vite entry from the app\'s build', function (): void {
    Vite::shouldReceive('asset')->with(TideThemePlugin::ENTRY)->andReturn('https://app.test/build/assets/theme-abc123.css');

    Themes::register(Theme::make('tide')->stylesheet('vite:'.TideThemePlugin::ENTRY));

    $head = headFor(NitpickPlugin::make()->default('tide'));

    expect($head)->toContain('id="packstub-theme-active" href="https://app.test/build/assets/theme-abc123.css" data-packstub-theme="tide"');
});

it('still renders when a Vite entry has not been built yet', function (): void {
    Themes::register(Theme::make('tide')->stylesheet('vite:'.TideThemePlugin::ENTRY));

    $head = headFor(NitpickPlugin::make()->default('tide'));

    expect($head)
        ->toContain('<meta name="packstub-theme" content="tide">')
        ->not->toContain('data-packstub-theme-stylesheet data-navigate-track');
});

it('renders the palettes ahead of the theme stylesheets, at the weight of Filament\'s own', function (): void {
    $head = headFor(NitpickPlugin::make()->default(Midnight::class)->picker());

    expect(strpos($head, 'nitpick-palettes'))->toBeLessThan(strpos($head, 'id="packstub-theme-active"'))
        ->and($head)->not->toContain('html[data-packstub-theme=');
});

it('fades a pick by default, and takes another transition or none', function (): void {
    expect(headFor(NitpickPlugin::make()->picker()))->toContain('"transition":"fade"')
        ->and(headFor(NitpickPlugin::make()->picker(transition: Transition::Sweep)))->toContain('"transition":"sweep"')
        ->and(headFor(NitpickPlugin::make()->picker(transition: 'slide')))->toContain('"transition":"slide"')
        ->and(headFor(NitpickPlugin::make()->picker(transition: false)))->toContain('"transition":null')
        // A later call that leaves the transition out keeps it.
        ->and(headFor(NitpickPlugin::make()->picker(transition: false)->picker(modal: false)))->toContain('"transition":null');

    config()->set('nitpick.transition', 'sweep');

    expect(headFor(NitpickPlugin::make()->picker()))->toContain('"transition":"sweep"');
});

it('renders the transition cover ahead of the body, and a pick that reloads leaves a note for the next page', function (): void {
    $head = headFor(NitpickPlugin::make()->picker());

    expect($head)
        ->toContain('<style id="nitpick-cover">html[data-nitpick-cover]::after {')
        ->toContain("var note = 'nitpick:cover';")
        ->toContain('sessionStorage.setItem(note,')
        ->toContain('pick: function (slug, options) {')
        ->toContain('reload: function () {')
        // The cover's shape animates only its transform or opacity, off the main thread; nothing is
        // painted through variables on <html>, which every element would inherit on every frame.
        ->toContain('shape.animate(transition.keyframes(coming)')
        ->toContain('sweep: { in: 400, out: 520, shape: sweep, keyframes: function () { return sweepKeyframes(); } }')
        // The sweep turns only through the angles where its edge crosses the panel, keyed by the
        // share of the panel it has passed, so it never leaps where it is far from its pivot.
        ->toContain('var from = Math.atan2(p[1] - h, p[0]) * degrees - 1, to = Math.atan2(p[1], p[0] - w) * degrees + 1;')
        ->toContain('if (sweptShare(mid) < i / steps) { lo = mid; } else { hi = mid; }')
        ->not->toContain('--nitpick-cover-clip')
        ->not->toContain('--nitpick-cover-opacity')
        // Skipped for visitors who prefer reduced motion.
        ->toContain("matchMedia('(prefers-reduced-motion: reduce)')")
        ->and(headFor(NitpickPlugin::make()))->not->toContain('nitpick-cover"');
});

it('holds the panel\'s own transitions while a swap runs, so the reveal shows the new theme settled', function (): void {
    $head = headFor(NitpickPlugin::make()->picker());

    expect($head)
        ->toContain('html[data-nitpick-still] *, html[data-nitpick-still] *::before, html[data-nitpick-still] *::after { transition: none !important; }')
        // On a live pick: from the theme change until the cover is gone.
        ->toMatch('/still\(true\);\s+var target = swap.slug;\s+var changed = apply\(target\);/')
        ->toContain('return animate(name, false).then(function () { still(false); });')
        // On a page a pick reloaded: from the first paint until the cover is gone.
        ->toMatch('/return reveal \? animate\(reveal, false\) : null;\s+\}\)\s+\.then\(function \(\) \{ still\(false\); \}\);/');
});

it('reveals the new theme only once the page has stopped changing under the cover', function (): void {
    expect(headFor(NitpickPlugin::make()->picker()))
        ->toContain('function settled(quietFor, cap) {')
        // A live pick waits for its stylesheets, its fonts and the saved pick's response.
        ->toContain('loaded(changed).then(fontsReady).then(function () { return settled(80, 900); }).then(lift);')
        // A reloaded page waits for Livewire, the reopened modal and its fonts, then for lazy
        // widgets: never longer than 1.8 s, then 0.7 s.
        ->toContain('var whole = Promise.all([started, reopened, fontsReady()]);')
        ->toContain('if (reloaded.reopen) { reopened = started.then(reopenPicker); }')
        ->toContain('.then(function () { return settled(120, 700); })');
});

it('changes the running swap\'s theme on a second pick instead of covering the panel again', function (): void {
    expect(headFor(NitpickPlugin::make()->picker()))
        ->toContain('if (swap) { swap.slug = slug; return true; }')
        ->toContain('if (swap.slug !== target) { return change(); }');
});

it('covers a theme that needs a page load only when a reload follows the pick', function (): void {
    expect(headFor(NitpickPlugin::make()->picker()))
        ->toContain('if (needsReload(slug) && ! (options && options.reload)) { return this.preview(slug); }');
});
