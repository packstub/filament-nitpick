<?php

use Packstub\Nitpick\Facades\Themes;

it('scopes every bundled stylesheet to its own theme', function (string $id): void {
    $css = file_get_contents(Themes::find($id)->getStylesheetPath());

    preg_match_all('/^([^\s@\/*}][^{]*)\{/m', $css, $rules);

    expect($rules[1])->not->toBeEmpty();

    foreach ($rules[1] as $selectors) {
        foreach (explode(',', $selectors) as $selector) {
            expect(trim($selector))->toContain('[data-packstub-theme="'.$id.'"]');
        }
    }
})->with(['aurora', 'midnight']);

// .fi-wi-widget is the grid cell around every widget, with the widget's own
// section, table or stats inside it: a surface on it draws a second card.
it('leaves the widget wrapper unstyled', function (string $id): void {
    expect(file_get_contents(Themes::find($id)->getStylesheetPath()))->not->toContain('.fi-wi-widget');
})->with(['aurora', 'midnight']);

// .fi-wi-stats-overview-stat-chart is the strip that holds a stat's sparkline:
// a background on it draws a solid band across the bottom of the card.
it('leaves the stat chart strip without a background', function (string $id): void {
    $css = file_get_contents(Themes::find($id)->getStylesheetPath());

    preg_match_all('/([^{}]*\.fi-wi-stats-overview-stat-chart[^{]*)\{([^}]*)\}/', $css, $rules, PREG_SET_ORDER);

    expect(array_filter($rules, fn (array $rule): bool => str_contains($rule[2], 'background')))->toBeEmpty();
})->with(['aurora', 'midnight']);

// A theme that puts a backdrop-filter, filter or transform on the topbar makes it the box a modal
// opened from the user menu is fixed to, and the modal is clipped to the bar (Mia does this).
it('keeps a modal opened from the topbar or sidebar from being clipped to the bar', function (): void {
    $css = file_get_contents(__DIR__.'/../../resources/css/nitpick.css');

    preg_match('/html \.fi-topbar-ctn:has\(\.fi-modal-open\),\s*html \.fi-topbar:has\(\.fi-modal-open\),\s*html \.fi-sidebar:has\(\.fi-modal-open\) \{([^}]*)\}/', $css, $rule);

    expect($rule[1] ?? '')
        ->toContain('backdrop-filter: none !important;')
        ->toContain('filter: none !important;')
        ->toContain('contain: none !important;')
        ->toContain('will-change: auto !important;')
        ->and($css)->toMatch('/html \.fi-topbar:has\(\.fi-modal-open\) \{\s*transform: none !important;/');
});

// A theme that gives its sidebar the modal's z-index (MeUI: 40) paints it over a modal opened from the
// user menu, which comes earlier in the DOM. The Appearance modal's overlay and window take the z-index
// Filament gives a modal stacked on another one, read from Filament's own modal stylesheet, so the two
// stay in step when Filament changes its stacking.
it('keeps the Appearance modal above a sidebar that shares the modal z-index', function (): void {
    $filament = file_get_contents(__DIR__.'/../../vendor/filament/support/resources/css/components/modal.css');

    preg_match('/\.fi-modal-open ~ \.fi-modal\.fi-modal-open \{\s*& > \.fi-modal-close-overlay,\s*& > \.fi-modal-window-ctn \{\s*@apply z-(\d+);/', $filament, $stacked);

    expect($stacked[1] ?? null)->not->toBeNull('Filament no longer raises a stacked modal with z-<n> on its overlay and window');

    $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(__DIR__.'/../../resources/css/nitpick.css'));

    preg_match_all('/([^{}]*\.fi-modal:has\(\.pst-theme-picker\)[^{]*)\{([^}]*)\}/', $css, $rules, PREG_SET_ORDER);

    $raised = array_filter($rules, fn (array $rule): bool => (bool) preg_match('/z-index:\s*'.$stacked[1].'\s*(;|$)/', $rule[2]));

    expect($raised)->toHaveCount(1);

    $selectors = array_map(trim(...), explode(',', reset($raised)[1]));

    expect($selectors)
        ->toContain('html .fi-modal:has(.pst-theme-picker) > .fi-modal-close-overlay')
        ->toContain('html .fi-modal:has(.pst-theme-picker) > .fi-modal-window-ctn');
});
