<?php

use Packstub\Nitpick\Support\PanelServing;

it('reads render hooks as Filament 5.12+ keeps them, a list of hooks with their scopes', function (): void {
    $a = fn (): string => 'a';
    $b = fn (): string => 'b';

    expect(PanelServing::renderHooksByScope([
        'panels::body.end' => [
            ['hook' => $a, 'scopes' => ['']],
            ['hook' => $b, 'scopes' => ['users', 'posts']],
        ],
    ]))->toBe([
        'panels::body.end' => [
            '' => [$a],
            'users' => [$b],
            'posts' => [$b],
        ],
    ]);
});

it('reads render hooks as earlier Filament 5 releases keep them, by scope', function (): void {
    $a = fn (): string => 'a';
    $b = fn (): string => 'b';

    expect(PanelServing::renderHooksByScope([
        'panels::body.end' => [
            '' => [$a],
            'users' => [$b],
        ],
    ]))->toBe([
        'panels::body.end' => [
            '' => [$a],
            'users' => [$b],
        ],
    ]);
});
