<?php

namespace Packstub\Themes\Tests\Fixtures;

use Filament\Support\Colors\Color;
use Packstub\Themes\Theme;

/**
 * A palette-only theme, the smallest thing a third party can register.
 */
class Ocean extends Theme
{
    public function getId(): string
    {
        return 'ocean';
    }

    public function getLabel(): string
    {
        return 'Ocean';
    }

    public function getColors(): array
    {
        return [
            'primary' => Color::Teal,
            'gray' => Color::Slate,
        ];
    }
}
