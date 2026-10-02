<?php

use Packstub\Themes\Facades\Themes;

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
