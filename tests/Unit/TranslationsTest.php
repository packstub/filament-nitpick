<?php

/**
 * @return array<string, string>
 */
function flattenStrings(array $strings, string $prefix = ''): array
{
    $flat = [];

    foreach ($strings as $key => $value) {
        is_array($value)
            ? $flat += flattenStrings($value, "{$prefix}{$key}.")
            : $flat["{$prefix}{$key}"] = $value;
    }

    return $flat;
}

/**
 * @return array<int, string>
 */
function placeholdersIn(string $string): array
{
    preg_match_all('/:[a-z_]+/', $string, $matches);
    sort($matches[0]);

    return $matches[0];
}

$languages = array_values(array_diff(scandir(__DIR__.'/../../resources/lang'), ['.', '..', 'en']));

it('ships every string English has, and no other, in each language', function (string $language): void {
    $english = flattenStrings(require __DIR__.'/../../resources/lang/en/themes.php');
    $translated = flattenStrings(require __DIR__."/../../resources/lang/{$language}/themes.php");

    expect(array_keys($translated))->toEqualCanonicalizing(array_keys($english));

    foreach ($english as $key => $string) {
        expect(trim($translated[$key]))->not->toBe('', "{$language}: {$key} is empty")
            ->and(placeholdersIn($translated[$key]))->toBe(placeholdersIn($string), "{$language}: {$key} keeps the placeholders");
    }
})->with($languages);

it('ships the languages 1.0 promises', function () use ($languages): void {
    expect($languages)->toEqualCanonicalizing(['ar', 'de', 'es', 'fr', 'it', 'nl', 'pt_BR', 'ro']);
});

it('reads the picker in the app\'s language', function (): void {
    app()->setLocale('de');

    expect(__('nitpick::themes.picker.default_hint', ['theme' => 'Aurora']))->toBe('Folgt Aurora')
        ->and(__('nitpick::themes.appearance.title'))->toBe('Darstellung');
});
